<?php

namespace App\Services;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TiivaApiService
{
    protected string $baseUrl;

    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) (config('services.tiiva.api_url') ?: config('services.tiiva.url', 'https://tiiva.ch/api')), '/');
        $this->token = config('services.tiiva.api_token') ?: config('services.tiiva.token');
    }

    protected function http(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token)
            ->accept('application/vnd.api+json')
            ->timeout(30);
    }

    /**
     * Vérifie la connectivité et la validité du Token auprès de l'API Tiiva.
     *
     * @return array{success: bool, organization: ?string, error: ?string}
     */
    public function testConnection(): array
    {
        if (empty($this->token)) {
            return [
                'success' => false,
                'organization' => null,
                'error' => 'Aucun token API Tiiva configuré (TIIVA_API_TOKEN dans .env).',
            ];
        }

        try {
            $response = $this->http()->get('/v1/me');

            if ($response->successful()) {
                $data = $response->json();
                $tenantName = $data['data']['attributes']['tenant']['name'] ?? $data['data']['attributes']['actor_name'] ?? 'Organisation Tiiva';

                return [
                    'success' => true,
                    'organization' => $tenantName,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'organization' => null,
                'error' => "Erreur HTTP {$response->status()} : ".($response->json('message') ?? $response->body()),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'organization' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Synchronise les groupes d'entraînement depuis Tiiva (/v1/contact-groups).
     *
     * @return array{created: int, updated: int, total: int}
     */
    public function syncGroups(): array
    {
        // Récupérer les groupes de contact depuis Tiiva
        $response = $this->http()->get('/v1/contact-groups', [
            'page[size]' => 100,
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Échec de la récupération des groupes Tiiva : '.$response->body());
        }

        $items = $response->json('data') ?? [];
        $created = 0;
        $updated = 0;

        foreach ($items as $item) {
            $tiivaId = (string) $item['id'];
            $attrs = $item['attributes'] ?? [];
            $name = trim((string) ($attrs['name'] ?? ''));

            if (empty($name)) {
                continue;
            }

            $group = Group::where('tiiva_id', $tiivaId)->first();

            if (! $group) {
                $group = Group::whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
            }

            if (! $group) {
                $baseSlug = Str::slug($name);
                $group = Group::where('slug', $baseSlug)->whereNull('tiiva_id')->first();
            }

            // Utilisation directe du statut officiel défini dans Tiiva
            $isTrainingGroupInTiiva = isset($attrs['is_activity_group'])
                ? (bool) $attrs['is_activity_group']
                : (! ($attrs['is_volunteers_group'] ?? false));

            if ($group) {
                $group->update([
                    'tiiva_id' => $tiivaId,
                    'name' => $name,
                    'color' => $attrs['color'] ?? $group->color,
                    'order' => $attrs['order'] ?? $group->order,
                    // Conserver le choix d'activation local s'il a été défini, sinon prendre la valeur officielle de Tiiva
                    'is_activity_group' => $group->is_activity_group ?? $isTrainingGroupInTiiva,
                ]);
                $updated++;
            } else {
                Group::create([
                    'tiiva_id' => $tiivaId,
                    'name' => $name,
                    'color' => $attrs['color'] ?? null,
                    'order' => $attrs['order'] ?? 0,
                    'is_activity_group' => $isTrainingGroupInTiiva,
                ]);
                $created++;
            }

            $syncedTiivaGroupIds[] = $tiivaId;
        }

        // Gestion des groupes supprimés ou disparus de Tiiva
        $deletedCount = 0;
        $deactivatedCount = 0;

        if (! empty($syncedTiivaGroupIds)) {
            $missingGroups = Group::whereNotNull('tiiva_id')
                ->whereNotIn('tiiva_id', $syncedTiivaGroupIds)
                ->get();

            foreach ($missingGroups as $missingGroup) {
                $hasHistory = $missingGroup->evaluations()->exists() || $missingGroup->athletes()->exists();

                if (! $hasHistory) {
                    $missingGroup->delete();
                    $deletedCount++;
                } else {
                    $missingGroup->update([
                        'is_activity_group' => false,
                    ]);
                    $deactivatedCount++;
                }
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deletedCount,
            'deactivated' => $deactivatedCount,
            'total' => count($items),
        ];
    }

    /**
     * Synchronise les athlètes et leurs responsables légaux (guardians) depuis Tiiva.
     * Interroge l'API Tiiva de manière ciblée par groupe d'entraînement (filter[group_id]),
     * évitant ainsi de télécharger inutilement les milliers de contacts non-athlètes (comité, bénévoles, sponsors).
     *
     * @param  Group|null  $specificGroup  Si renseigné, synchronise uniquement ce groupe.
     * @return array{created: int, updated: int, deactivated: int, total: int, newly_created_ids: array<int>}
     */
    public function syncContacts(?Group $specificGroup = null): array
    {
        // S'assurer que les groupes locaux ont bien leur tiiva_id
        if (Group::whereNotNull('tiiva_id')->count() === 0) {
            $this->syncGroups();
        }

        $targetGroups = $specificGroup
            ? collect([$specificGroup])
            : Group::where('is_activity_group', true)->whereNotNull('tiiva_id')->get();

        $created = 0;
        $updated = 0;
        $total = 0;
        $syncedActiveTiivaIds = [];
        $newlyCreatedAthleteIds = [];

        foreach ($targetGroups as $group) {
            if (empty($group->tiiva_id)) {
                continue;
            }

            $page = 1;

            do {
                $response = $this->http()->get('/v1/contacts', [
                    'filter[group_id]' => $group->tiiva_id,
                    'page[number]' => $page,
                    'page[size]' => 100,
                    'include' => 'groups,guardians',
                ]);

                if (! $response->successful()) {
                    // Si l'API renvoie une erreur sur un groupe spécifique, lever ou continuer
                    break;
                }

                $data = $response->json();
                $items = $data['data'] ?? [];

                if (empty($items)) {
                    break;
                }

                foreach ($items as $contact) {
                    $total++;
                    $tiivaId = (string) $contact['id'];
                    $attrs = $contact['attributes'] ?? [];
                    $relationships = $contact['relationships'] ?? [];

                    $firstName = trim((string) ($attrs['first_name'] ?? ''));
                    $lastName = trim((string) ($attrs['last_name'] ?? ''));

                    if (empty($firstName) || empty($lastName)) {
                        continue;
                    }

                    // Date de naissance et année
                    $birthday = null;
                    $birthYear = (int) date('Y');
                    if (! empty($attrs['birthday'])) {
                        try {
                            $birthday = Carbon::parse($attrs['birthday']);
                            $birthYear = (int) $birthday->format('Y');
                        } catch (\Throwable) {
                            // ignore
                        }
                    }

                    // Date d'entrée / inscription au club
                    $entryDate = null;
                    if (! empty($attrs['entry_date'])) {
                        try {
                            $entryDate = Carbon::parse($attrs['entry_date']);
                        } catch (\Throwable) {
                        }
                    }

                    // Récupération des responsables légaux (guardians)
                    $guardianIds = array_map('strval', array_column($relationships['guardians']['data'] ?? [], 'id'));

                    // Statut de l'athlète dans Tiiva
                    $memberStatus = strtolower((string) ($attrs['member_status'] ?? 'active'));
                    $status = ($memberStatus === 'inactive' || $memberStatus === 'passive' || ! ($attrs['is_active'] ?? true))
                        ? AthleteStatus::Inactive
                        : AthleteStatus::Active;

                    // Un athlète est en période d'adaptation s'il a rejoint le club très récemment.
                    // Sinon, c'est un membre actif établi du club.
                    $recentEntryMonths = (int) config('evaluation.adaptation.recent_entry_months', 3);
                    $isRecentEntry = $entryDate ? $entryDate->isAfter(now()->subMonths($recentEntryMonths)) : false;
                    $initialStatus = ($status === AthleteStatus::Inactive)
                        ? AthleteStatus::Inactive
                        : ($isRecentEntry ? AthleteStatus::Adaptation : AthleteStatus::Active);

                    if ($status === AthleteStatus::Active) {
                        $syncedActiveTiivaIds[] = $tiivaId;
                    }

                    // Recherche et mise à jour / création de l'athlète
                    $athlete = Athlete::where('tiiva_id', $tiivaId)->first();

                    if (! $athlete) {
                        $athlete = Athlete::whereRaw('LOWER(first_name) = ? AND LOWER(last_name) = ? AND birth_year = ?', [
                            mb_strtolower($firstName),
                            mb_strtolower($lastName),
                            $birthYear,
                        ])->first();
                    }

                    $athleteData = [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'birth_year' => $birthYear,
                        'birthday' => $birthday?->format('Y-m-d'),
                        'entry_date' => $entryDate?->format('Y-m-d'),
                        'gender' => $attrs['gender'] ?? null,
                        'email' => $attrs['email'] ?? null,
                        'phone' => $attrs['phone'] ?? null,
                        'license_number' => $attrs['licence_id'] ?? null,
                        'nds_number' => ! empty($attrs['nds_id']) ? (string) $attrs['nds_id'] : null,
                        'guardian_tiiva_ids' => $guardianIds,
                        'group_id' => $group->id,
                        'tiiva_id' => $tiivaId,
                    ];

                    if ($athlete) {
                        $statusToSet = ($status === AthleteStatus::Inactive) ? AthleteStatus::Inactive : $athlete->status;
                        $athlete->update([
                            ...$athleteData,
                            'status' => $statusToSet,
                        ]);
                        $updated++;
                    } else {
                        $newAthlete = Athlete::create([
                            ...$athleteData,
                            'status' => $initialStatus,
                        ]);
                        $newlyCreatedAthleteIds[] = $newAthlete->id;
                        $created++;
                    }
                }

                $nextPage = $data['links']['next'] ?? null;
                $page++;
            } while ($nextPage !== null);
        }

        // Désactivation des athlètes locaux rattachés aux groupes synchronisés qui ne sont plus retournés par Tiiva
        $deactivatedCount = 0;
        if (! empty($syncedActiveTiivaIds) && ! $specificGroup) {
            $athletesToDeactivate = Athlete::whereNotNull('tiiva_id')
                ->whereNotIn('tiiva_id', $syncedActiveTiivaIds)
                ->where('status', '!=', AthleteStatus::Inactive)
                ->get();

            foreach ($athletesToDeactivate as $ath) {
                $ath->update(['status' => AthleteStatus::Inactive]);
                $deactivatedCount++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'deactivated' => $deactivatedCount,
            'total' => $total,
            'newly_created_ids' => $newlyCreatedAthleteIds,
        ];
    }

    /**
     * Effectue une synchronisation complète (Groupes + Athlètes) et initialise la session d'évaluation si fournie.
     * Place automatiquement les nouveaux athlètes en statut 'Adaptation' (Période d'observation).
     *
     * @return array{
     *     success: bool,
     *     groups_synced: int,
     *     athletes_synced: int,
     *     athletes_created: int,
     *     athletes_updated: int,
     *     athletes_deactivated: int,
     *     evaluations_created: int,
     *     evaluations_adaptation_created: int,
     *     error: ?string
     * }
     */
    public function syncAll(?EvaluationSession $session = null): array
    {
        try {
            $groupsResult = $this->syncGroups();
            $contactsResult = $this->syncContacts();
            $evalsCreated = 0;
            $adaptationEvalsCreated = 0;

            if ($session) {
                $session->update(['last_tiiva_synced_at' => now()]);

                $athletesQuery = Athlete::whereIn('status', [AthleteStatus::Active, AthleteStatus::Adaptation])->with('group');
                if ($session->groups()->exists()) {
                    $athletesQuery->whereIn('group_id', $session->groups()->pluck('groups.id'));
                }
                $athletesToEvaluate = $athletesQuery->get();

                foreach ($athletesToEvaluate as $athlete) {
                    $existing = Evaluation::where('evaluation_session_id', $session->id)
                        ->where('athlete_id', $athlete->id)
                        ->first();

                    if (! $existing) {
                        $group = $athlete->group;
                        $context = ($athlete->status === AthleteStatus::Adaptation)
                            ? EvaluationContext::Adaptation
                            : EvaluationContext::Collective;

                        Evaluation::create([
                            'athlete_id' => $athlete->id,
                            'group_id' => $athlete->group_id,
                            'evaluation_session_id' => $session->id,
                            'context' => $context,
                            'start_date' => $session->start_date,
                            'end_date' => $session->end_date,
                            'weeks_count' => $session->weeks_count,
                            'sessions_per_week' => $group?->default_sessions_per_week ?? 3,
                            'competitions_planned' => $group?->default_competitions_planned ?? 6,
                        ]);

                        $evalsCreated++;
                        if ($context === EvaluationContext::Adaptation) {
                            $adaptationEvalsCreated++;
                        }
                    } else {
                        $existing->update([
                            'group_id' => $athlete->group_id,
                            'start_date' => $session->start_date,
                            'end_date' => $session->end_date,
                            'weeks_count' => $session->weeks_count,
                        ]);
                    }
                }
            }

            return [
                'success' => true,
                'groups_synced' => $groupsResult['total'],
                'athletes_synced' => $contactsResult['total'],
                'athletes_created' => $contactsResult['created'],
                'athletes_updated' => $contactsResult['updated'],
                'athletes_deactivated' => $contactsResult['deactivated'],
                'evaluations_created' => $evalsCreated,
                'evaluations_adaptation_created' => $adaptationEvalsCreated,
                'error' => null,
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'groups_synced' => 0,
                'athletes_synced' => 0,
                'athletes_created' => 0,
                'athletes_updated' => 0,
                'athletes_deactivated' => 0,
                'evaluations_created' => 0,
                'evaluations_adaptation_created' => 0,
                'error' => $e->getMessage(),
            ];
        }
    }
}
