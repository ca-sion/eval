<?php

namespace App\Filament\Resources\EvaluationSessions\Pages;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Filament\Resources\EvaluationSessions\EvaluationSessionResource;
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
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;

class EditEvaluationSession extends EditRecord
{
    protected static string $resource = EvaluationSessionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var EvaluationSession $session */
        $session = $this->record;

        return [
            Action::make('workflow')
                ->label('Piloter le workflow')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->url(fn () => EvaluationSessionResource::getUrl('workflow', ['record' => $session])),

            Action::make('initialize_evaluations')
                ->label('Initialiser pour tous les groupes')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Générer les évaluations pour les athlètes actifs ?')
                ->modalDescription('Une évaluation sera créée pour chaque athlète actif dans son groupe d\'entraînement.')
                ->action(function () use ($session): void {
                    $hasTargetedGroups = $session->groups()->exists();
                    $targetedGroupIds = $hasTargetedGroups ? $session->groups()->pluck('groups.id')->toArray() : [];

                    $athletesQuery = Athlete::whereIn('status', [AthleteStatus::Active, AthleteStatus::Adaptation])->with('group');
                    if ($hasTargetedGroups) {
                        $athletesQuery->whereIn('group_id', $targetedGroupIds);
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
                }),

            Action::make('recalculate_arbitrate')
                ->label('Recalculer et arbitrer')
                ->icon(Heroicon::OutlinedScale)
                ->color('gray')
                ->action(function () use ($session): void {
                    $calculator = app(EvaluationCalculatorService::class);
                    $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))->get();

                    foreach ($groups as $group) {
                        $calculator->arbitrateGroup($group, $session);
                    }

                    Notification::make()
                        ->title('Sélection arbitrée avec succès')
                        ->body("Les rangs, moyennes et décisions ont été recalculés pour l'ensemble des groupes.")
                        ->success()
                        ->send();
                }),

            ActionGroup::make([
                Action::make('sync_tiiva_api')
                    ->label('Synchroniser via l\'API Tiiva')
                    ->icon(Heroicon::OutlinedCloudArrowDown)
                    ->color('primary')
                    ->requiresConfirmation()
                    ->modalHeading('Synchroniser groupes et athlètes depuis Tiiva ?')
                    ->modalDescription('Cette action contacte directement l\'API REST de Tiiva pour mettre à jour les groupes d\'entraînement, les athlètes actifs et leurs liaisons avec les responsables légaux.')
                    ->action(function () use ($session): void {
                        $apiService = app(TiivaApiService::class);
                        $result = $apiService->syncAll($session);

                        if (! ($result['success'] ?? false)) {
                            Notification::make()
                                ->title('Échec de la synchronisation API Tiiva')
                                ->body($result['error'] ?? 'Erreur inconnue')
                                ->danger()
                                ->send();

                            return;
                        }

                        $calculator = app(EvaluationCalculatorService::class);
                        $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))->get();
                        foreach ($groups as $group) {
                            $calculator->arbitrateGroup($group, $session);
                        }

                        Notification::make()
                            ->title('Synchronisation API Tiiva réussie')
                            ->body("{$result['groups_synced']} groupes, {$result['athletes_synced']} athlètes synchronisés et {$result['evaluations_created']} évaluations créées.")
                            ->success()
                            ->send();
                    }),

                Action::make('import_volunteering')
                    ->label('Importer matrice bénévolats (Excel)')
                    ->icon(Heroicon::OutlinedHeart)
                    ->form([
                        FileUpload::make('file')
                            ->label('Classeur des participations bénévoles Tiiva (.xlsx)')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->disk('local')
                            ->directory('imports')
                            ->required(),
                    ])
                    ->action(function (array $data) use ($session): void {
                        $filePath = Storage::disk('local')->path($data['file']);
                        $importer = app(VolunteerImportService::class);
                        $calculator = app(EvaluationCalculatorService::class);
                        $result = $importer->import($filePath, $session, $calculator);

                        $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))->get();
                        foreach ($groups as $group) {
                            $calculator->arbitrateGroup($group, $session);
                        }

                        $msg = "{$result['synced']} athlètes crédités de points bénévolat.";
                        if ($result['unmatched'] > 0) {
                            $msg .= " ({$result['unmatched']} participations sans athlète rattaché)";
                        }

                        Notification::make()
                            ->title('Importation des bénévolats terminée')
                            ->body($msg)
                            ->info()
                            ->send();
                    }),

                Action::make('import_tiiva')
                    ->label('Importer fichier export Tiiva (Excel ou CSV)')
                    ->icon(Heroicon::OutlinedArrowUpTray)
                    ->form([
                        FileUpload::make('file')
                            ->label('Fichier export Tiiva (.xlsx ou .csv)')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/csv', 'text/plain', 'application/vnd.ms-excel'])
                            ->disk('local')
                            ->directory('imports')
                            ->required(),
                    ])
                    ->action(function (array $data) use ($session): void {
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
                            ->body("{$result['created']} créés, {$result['updated']} mis à jour, {$result['groups_created']} groupes créés.")
                            ->success()
                            ->send();
                    }),

                Action::make('import_nds')
                    ->label('Importer les présences NDS (Excel)')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->form([
                        FileUpload::make('file')
                            ->label('Classeur officiel NDS Jeunesse+Sport (.xlsx)')
                            ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                            ->disk('local')
                            ->directory('imports')
                            ->required(),
                    ])
                    ->action(function (array $data) use ($session): void {
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
                        $msg = "{$result['synced']} athlètes synchronisés avec succès.";
                        if ($unmatchedCount > 0) {
                            $msg .= " ({$unmatchedCount} non réconciliés : ".implode(', ', array_slice($result['unmatched'], 0, 3)).'...)';
                        }

                        Notification::make()
                            ->title('Import NDS terminé')
                            ->body($msg)
                            ->info()
                            ->send();
                    }),
            ])
                ->label('Imports de données')
                ->icon(Heroicon::OutlinedArrowDownTray),

            ActionGroup::make([
                Action::make('export_pdf_comite')
                    ->label('Procès-verbal (PDF)')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->url(fn () => route('evaluation-sessions.minutes.pdf', $session))
                    ->openUrlInNewTab(),

                Action::make('export_excel')
                    ->label('Evaluations (Excel)')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->action(fn () => app(ExcelExportService::class)->exportSession($session)),
            ])
                ->label('Exports et documents')
                ->icon(Heroicon::OutlinedArrowUpOnSquare),

            DeleteAction::make(),
        ];
    }
}
