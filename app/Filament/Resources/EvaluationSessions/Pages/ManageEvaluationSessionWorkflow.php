<?php

namespace App\Filament\Resources\EvaluationSessions\Pages;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Filament\Resources\EvaluationSessions\EvaluationSessionResource;
use App\Filament\Resources\EvaluationSessions\RelationManagers\EvaluationsRelationManager;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use App\Services\EvaluationCalculatorService;
use App\Services\ExcelExportService;
use App\Services\NdsImportService;
use App\Services\TiivaApiService;
use App\Services\TiivaImportService;
use App\Services\VolunteerImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @property EvaluationSession $record
 */
class ManageEvaluationSessionWorkflow extends Page
{
    use InteractsWithRecord;

    protected static string $resource = EvaluationSessionResource::class;

    protected string $view = 'filament.resources.evaluation-sessions.pages.manage-evaluation-session-workflow';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function getTitle(): string|Htmlable
    {
        return "Workflow et pilotage : {$this->record->title}";
    }

    public function getBreadcrumbs(): array
    {
        return [
            EvaluationSessionResource::getUrl('index') => 'Sessions d\'évaluation',
            '#' => $this->record->title,
            '' => 'Pilotage du workflow',
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            EvaluationsRelationManager::class,
        ];
    }

    /**
     * Fournit les statistiques complètes de la session à la vue.
     *
     * @return array<string, mixed>
     */
    public function getStatsProperty(): array
    {
        return app(EvaluationCalculatorService::class)->getSessionProgressStats($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->getSyncTiivaApiAction(),
            $this->getInitializeEvaluationsAction(),
            $this->getImportTiivaAction(),
            $this->getImportNdsAction(),
            $this->getImportVolunteeringAction(),
            $this->getRecalculateArbitrateAction(),
            $this->getExportPdfMinutesAction(),
            $this->getExportExcelAction(),
            $this->getToggleSessionClosedAction(),
            $this->getEditSessionAction(),
        ];
    }

    public function getCachedHeaderActions(): array
    {
        return array_filter([
            $this->getAction('edit_session'),
            $this->getAction('toggle_session_closed'),
        ]);
    }

    /**
     * Action Étape 1 : Synchroniser directement avec l'API REST de Tiiva.
     */
    public function getSyncTiivaApiAction(): Action
    {
        return Action::make('sync_tiiva_api')
            ->label('Synchroniser avec l\'API Tiiva')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('primary')
            ->requiresConfirmation()
            ->modalHeading('Synchroniser groupes, athlètes et responsables légaux depuis Tiiva ?')
            ->modalDescription('Cette action interroge l\'API officielle de Tiiva, met à jour les effectifs, enregistre les responsables légaux pour le bénévolat et prépare les évaluations.')
            ->action(function (): void {
                try {
                    $api = app(TiivaApiService::class);
                    $result = $api->syncAll($this->record);

                    if (! ($result['success'] ?? false)) {
                        Notification::make()
                            ->title('Échec de la synchronisation Tiiva')
                            ->body($result['error'] ?? 'Erreur inconnue')
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Synchronisation Tiiva réussie')
                        ->body("{$result['groups_synced']} groupes analysés • {$result['athletes_synced']} athlètes synchronisés ({$result['athletes_created']} créés, {$result['athletes_updated']} màj) • {$result['evaluations_created']} évaluations créées.")
                        ->success()
                        ->send();
                } catch (\Throwable $e) {
                    Notification::make()
                        ->title('Erreur lors de la synchronisation Tiiva')
                        ->body($e->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }

    /**
     * Action Étape 1 : Initialiser les évaluations pour tous les athlètes actifs existants.
     */
    public function getInitializeEvaluationsAction(): Action
    {
        return Action::make('initialize_evaluations')
            ->label('Initialiser les évaluations de la session')
            ->icon(Heroicon::OutlinedSparkles)
            ->color('gray')
            ->requiresConfirmation()
            ->modalHeading('Générer les évaluations pour tous les athlètes actifs ?')
            ->modalDescription('Une évaluation collective sera créée pour chaque athlète actif dans son groupe d\'entraînement actuel.')
            ->action(function (): void {
                $session = $this->record;
                $athletesQuery = Athlete::whereIn('status', [AthleteStatus::Active, AthleteStatus::Adaptation])->with('group');
                if ($session->groups()->exists()) {
                    $athletesQuery->whereIn('group_id', $session->groups()->pluck('groups.id'));
                }
                $athletes = $athletesQuery->get();
                $createdCount = 0;
                $updatedCount = 0;

                foreach ($athletes as $athlete) {
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

                        $createdCount++;
                    } else {
                        $existing->update([
                            'group_id' => $athlete->group_id,
                            'start_date' => $session->start_date,
                            'end_date' => $session->end_date,
                            'weeks_count' => $session->weeks_count,
                        ]);
                        $updatedCount++;
                    }
                }

                $msg = $createdCount > 0
                    ? "{$createdCount} nouvelles évaluations créées ({$updatedCount} évaluations existantes synchronisées)."
                    : "{$updatedCount} évaluations existantes synchronisées avec les dates de la session.";

                Notification::make()
                    ->title('Initialisation et synchronisation terminées')
                    ->body($msg)
                    ->success()
                    ->send();
            });
    }

    /**
     * Action Étape 1 : Importer fichier Tiiva (Excel ou CSV - Secours).
     */
    public function getImportTiivaAction(): Action
    {
        return Action::make('import_tiiva')
            ->label('Importer fichier Tiiva (Excel ou CSV)')
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->color('gray')
            ->form([
                FileUpload::make('file')
                    ->label('Fichier export Tiiva (.xlsx ou .csv)')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->disk('local')
                    ->directory('imports')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $session = $this->record;
                $filePath = Storage::disk('local')->path($data['file']);
                $importer = app(TiivaImportService::class);
                $result = $importer->import($filePath, $session);

                // Recalculer l'arbitrage
                $calculator = app(EvaluationCalculatorService::class);
                $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))->get();
                foreach ($groups as $group) {
                    $calculator->arbitrateGroup($group, $session);
                }

                Notification::make()
                    ->title('Import Tiiva effectué')
                    ->body("{$result['created']} athlètes créés, {$result['updated']} mis à jour, {$result['groups_created']} groupes créés.")
                    ->success()
                    ->send();
            });
    }

    /**
     * Action Étape 3 : Importer présences NDS Jeunesse+Sport.
     */
    public function getImportNdsAction(): Action
    {
        return Action::make('import_nds')
            ->label('Importer le classeur officiel NDS (Excel)')
            ->icon(Heroicon::OutlinedDocumentCheck)
            ->color('gray')
            ->form([
                FileUpload::make('file')
                    ->label('Classeur officiel NDS Jeunesse+Sport (.xlsx)')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                    ->disk('local')
                    ->directory('imports')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $session = $this->record;
                $filePath = Storage::disk('local')->path($data['file']);
                $importer = app(NdsImportService::class);
                $calculator = app(EvaluationCalculatorService::class);
                $result = $importer->import($filePath, $session, $calculator);

                // Recalculer l'arbitrage
                $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))->get();
                foreach ($groups as $group) {
                    $calculator->arbitrateGroup($group, $session);
                }

                $unmatchedCount = count($result['unmatched']);
                $msg = "{$result['synced']} athlètes synchronisés avec succès selon le calendrier de la session.";
                if ($unmatchedCount > 0) {
                    $msg .= " ({$unmatchedCount} non réconciliés : ".implode(', ', array_slice($result['unmatched'], 0, 3)).'...)';
                }

                Notification::make()
                    ->title('Import NDS terminé')
                    ->body($msg)
                    ->info()
                    ->send();
            });
    }

    /**
     * Action Étape 3bis : Importer matrice des participations bénévoles (Excel Tiiva).
     */
    public function getImportVolunteeringAction(): Action
    {
        return Action::make('import_volunteering')
            ->label('Importer la matrice des bénévolats (Excel)')
            ->icon(Heroicon::OutlinedHandRaised)
            ->color('gray')
            ->form([
                FileUpload::make('file')
                    ->label('Matrice des participations Tiiva (.xlsx ou .csv)')
                    ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'text/plain', 'application/vnd.ms-excel'])
                    ->disk('local')
                    ->directory('imports')
                    ->required(),
            ])
            ->action(function (array $data): void {
                $session = $this->record;
                $filePath = Storage::disk('local')->path($data['file']);
                $importer = app(VolunteerImportService::class);
                $calculator = app(EvaluationCalculatorService::class);
                $result = $importer->import($filePath, $session, $calculator);

                // Recalculer l'arbitrage
                $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))->get();
                foreach ($groups as $group) {
                    $calculator->arbitrateGroup($group, $session);
                }

                Notification::make()
                    ->title('Import des bénévolats terminé')
                    ->body("{$result['synced']} athlètes mis à jour avec les participations de leurs responsables légaux ({$result['total_missions']} missions réconciliées).")
                    ->success()
                    ->send();
            });
    }

    /**
     * Action Étape 4 : Recalculer et arbitrer la sélection globale.
     */
    public function getRecalculateArbitrateAction(): Action
    {
        return Action::make('recalculate_arbitrate')
            ->label('Calculer et arbitrer la sélection')
            ->icon(Heroicon::OutlinedScale)
            ->color('primary')
            ->action(function (): void {
                $session = $this->record;
                $calculator = app(EvaluationCalculatorService::class);
                $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))->get();

                foreach ($groups as $group) {
                    $calculator->arbitrateGroup($group, $session);
                }

                Notification::make()
                    ->title('Sélection arbitrée avec succès')
                    ->body('Les moyennes, rangs et décisions ont été mis à jour pour tous les groupes.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Action Étape 5 : Télécharger Procès-Verbal officiel Comité (PDF).
     */
    public function getExportPdfMinutesAction(): Action
    {
        return Action::make('export_pdf_minutes')
            ->label('Procès-verbal officiel du comité (PDF)')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->url(fn () => route('evaluation-sessions.minutes.pdf', $this->record))
            ->openUrlInNewTab();
    }

    /**
     * Action Étape 5 : Exporter Classeur de résultats complet (Excel multi-onglets).
     */
    public function getExportExcelAction(): Action
    {
        return Action::make('export_excel')
            ->label('Classeur de résultats (Excel)')
            ->icon(Heroicon::OutlinedTableCells)
            ->color('gray')
            ->action(function (): BinaryFileResponse {
                return app(ExcelExportService::class)->exportSession($this->record);
            });
    }

    /**
     * Action : Clôturer / Rouvrir la session.
     */
    public function getToggleSessionClosedAction(): Action
    {
        return Action::make('toggle_session_closed')
            ->label(fn () => $this->record->is_closed ? 'Rouvrir la session' : 'Clôturer la session')
            ->icon(fn () => $this->record->is_closed ? Heroicon::OutlinedLockOpen : Heroicon::OutlinedLockClosed)
            ->color(fn () => $this->record->is_closed ? 'gray' : 'danger')
            ->requiresConfirmation()
            ->modalHeading(fn () => $this->record->is_closed ? 'Rouvrir la session d\'évaluation ?' : 'Clôturer définitivement la session ?')
            ->modalDescription(fn () => $this->record->is_closed
                ? 'Les entraîneurs pourront à nouveau saisir ou modifier les notes sur mobile.'
                : 'La saisie mobile sera définitivement verrouillée pour cette session. Les notes seront figées pour le Comité.')
            ->action(function (): void {
                $this->record->is_closed = ! $this->record->is_closed;
                $this->record->save();

                Notification::make()
                    ->title($this->record->is_closed ? 'Session clôturée' : 'Session rouverte')
                    ->body($this->record->is_closed ? 'La saisie mobile est désormais verrouillée.' : 'La saisie mobile est de nouveau accessible.')
                    ->success()
                    ->send();
            });
    }

    /**
     * Action : Modifier les paramètres de la session.
     */
    public function getEditSessionAction(): Action
    {
        return Action::make('edit_session')
            ->label('Paramètres de la session')
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->color('gray')
            ->fillForm([
                'title' => $this->record->title,
                'start_date' => $this->record->start_date,
                'end_date' => $this->record->end_date,
                'submission_deadline' => $this->record->submission_deadline,
                'weeks_count' => $this->record->weeks_count,
                'groups' => $this->record->groups()->pluck('groups.id')->toArray(),
            ])
            ->form([
                TextInput::make('title')
                    ->label('Intitulé de la session')
                    ->required(),
                DatePicker::make('start_date')
                    ->label('Date de début de période')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Date de fin de période')
                    ->required(),
                DatePicker::make('submission_deadline')
                    ->label('Date limite de rendu pour les entraîneurs')
                    ->helperText('Date limite accordée aux entraîneurs pour transmettre leurs évaluations (si vide, la date de fin sera appliquée)'),
                TextInput::make('weeks_count')
                    ->label('Durée (nombre de semaines)')
                    ->numeric()
                    ->required(),
                CheckboxList::make('groups')
                    ->relationship('groups', 'name')
                    ->label('Groupes concernés')
                    ->helperText('Sélectionner les groupes ciblés. Laisser vide pour inclure tous les groupes.')
                    ->bulkToggleable()
                    ->columns(2),
            ])
            ->action(function (array $data): void {
                $groups = $data['groups'] ?? [];
                unset($data['groups']);
                $this->record->update($data);
                $this->record->groups()->sync($groups);

                Notification::make()
                    ->title('Paramètres de la session mis à jour')
                    ->success()
                    ->send();
            });
    }
}
