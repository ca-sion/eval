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
use App\Services\PdfReportService;
use App\Services\TiivaImportService;
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
            Action::make('initialize_evaluations')
                ->label('Initialiser pour tous les groupes')
                ->icon(Heroicon::OutlinedSparkles)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Générer les évaluations pour tous les athlètes actifs ?')
                ->modalDescription('Une fiche d\'évaluation collective sera créée pour chaque athlète actif dans son groupe d\'entraînement actuel.')
                ->action(function () use ($session): void {
                    $activeAthletes = Athlete::where('status', AthleteStatus::Active)->with('group')->get();
                    $createdCount = 0;

                    foreach ($activeAthletes as $athlete) {
                        $exists = Evaluation::where('evaluation_session_id', $session->id)
                            ->where('athlete_id', $athlete->id)
                            ->exists();

                        if (! $exists) {
                            $group = $athlete->group;

                            Evaluation::create([
                                'athlete_id' => $athlete->id,
                                'group_id' => $athlete->group_id,
                                'evaluation_session_id' => $session->id,
                                'context' => EvaluationContext::Collective,
                                'start_date' => $session->start_date,
                                'end_date' => $session->end_date,
                                'weeks_count' => $session->weeks_count,
                                'sessions_per_week' => $group?->default_sessions_per_week ?? 3,
                                'competitions_planned' => $group?->default_competitions_planned ?? 6,
                            ]);

                            $createdCount++;
                        }
                    }

                    Notification::make()
                        ->title('Initialisation terminée')
                        ->body("{$createdCount} fiches d'évaluation créées pour la session.")
                        ->success()
                        ->send();
                }),

            Action::make('recalculate_arbitrate')
                ->label('Recalculer & Arbitrer')
                ->icon(Heroicon::OutlinedScale)
                ->color('success')
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
                Action::make('import_tiiva')
                    ->label('Importer données Tiiva (Excel/CSV)')
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
                    ->label('Importer présences NDS (Excel)')
                    ->icon(Heroicon::OutlinedDocumentCheck)
                    ->form([
                        FileUpload::make('file')
                            ->label('Classeur officiel NDS J+S (.xlsx)')
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
                ->label('Imports')
                ->icon(Heroicon::OutlinedArrowDownTray),

            ActionGroup::make([
                Action::make('export_pdf_comite')
                    ->label('Procès-Verbal officiel Comité (PDF)')
                    ->icon(Heroicon::OutlinedDocumentText)
                    ->color('danger')
                    ->action(fn () => app(PdfReportService::class)->generateOfficialSessionMinutes($session)),

                Action::make('export_excel')
                    ->label('Classeur de résultats (Excel multi-onglets)')
                    ->icon(Heroicon::OutlinedTableCells)
                    ->color('success')
                    ->action(fn () => app(ExcelExportService::class)->exportSession($session)),
            ])
                ->label('Exports officiels')
                ->icon(Heroicon::OutlinedArrowUpOnSquare),

            DeleteAction::make(),
        ];
    }
}
