<?php

namespace App\Filament\Resources\Athletes\RelationManagers;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationDecision;
use App\Models\Evaluation;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;

class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluations';

    protected static ?string $title = 'Historique des évaluations et périodes probatoires';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('context')
                ->options(EvaluationContext::class)
                ->required()
                ->label('Contexte d\'évaluation'),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('context')
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('evaluationSession.title')
                    ->label('Session')
                    ->placeholder('Évaluation individuelle')
                    ->weight('bold')
                    ->searchable(),

                SelectColumn::make('context')
                    ->label('Contexte')
                    ->options(EvaluationContext::class)
                    ->rules(['required']),

                TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('end_date')
                    ->label('Échéance')
                    ->date('d.m.Y')
                    ->sortable(),

                TextColumn::make('final_score')
                    ->label('Note finale')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).' / 10' : '-')
                    ->badge()
                    ->color(fn ($state, Evaluation $record) => $state !== null && (float) $state >= (float) ($record->group?->min_score ?? 6.5) ? 'success' : 'danger'),

                TextColumn::make('decision')
                    ->label('Décision')
                    ->badge(),

                TextColumn::make('group.name')
                    ->label('Groupe')
                    ->badge()
                    ->color('info'),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('interview_pdf')
                        ->label('Fiche PDF')
                        ->icon(Heroicon::OutlinedDocumentArrowDown)
                        ->color('gray')
                        ->tooltip('Télécharger la fiche d\'entretien (PDF)')
                        ->url(fn (Evaluation $record) => route('evaluations.pdf', $record))
                        ->openUrlInNewTab(),

                    Action::make('probation')
                        ->label('Sursis probatoire (art. 10.5)')
                        ->icon(Heroicon::OutlinedClock)
                        ->color('gray')
                        ->visible(fn (Evaluation $record) => $record->decision === EvaluationDecision::ProbationNeeded || ($record->final_score !== null && $record->final_score < 6.5))
                        ->form([
                            DatePicker::make('start_date')
                                ->label('Date de début du sursis')
                                ->default(Carbon::tomorrow())
                                ->required(),
                        ])
                        ->action(function (Evaluation $record, array $data): void {
                            $startDate = Carbon::parse($data['start_date']);
                            $weeksCount = (int) config('evaluation.durations.evaluation_probation_weeks', 2);
                            $endDate = $startDate->copy()->addWeeks($weeksCount);

                            Evaluation::create([
                                'athlete_id' => $record->athlete_id,
                                'group_id' => $record->group_id,
                                'parent_evaluation_id' => $record->id,
                                'context' => EvaluationContext::EvaluationProbation,
                                'start_date' => $startDate,
                                'end_date' => $endDate,
                                'weeks_count' => $weeksCount,
                                'sessions_per_week' => $record->sessions_per_week,
                                'competitions_planned' => $record->competitions_planned,
                            ]);

                            $record->athlete->update(['status' => AthleteStatus::Probation]);

                            Notification::make()
                                ->title("Sursis probatoire de {$weeksCount} semaines enclenché")
                                ->body("Période d'observation jusqu'au ".$endDate->format('d.m.Y').'.')
                                ->success()
                                ->send();
                        }),

                    Action::make('validate_athlete')
                        ->label('Valider définitivement (art. 10)')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('gray')
                        ->requiresConfirmation()
                        ->modalHeading('Valider l\'admission définitive de l\'athlète ?')
                        ->visible(fn (Evaluation $record) => in_array($record->context, [EvaluationContext::Adaptation, EvaluationContext::EvaluationProbation, EvaluationContext::DisciplinaryProbation]))
                        ->action(function (Evaluation $record): void {
                            $record->athlete->update(['status' => AthleteStatus::Active]);
                            Notification::make()
                                ->title('Athlète validé définitivement')
                                ->body("{$record->athlete->full_name} est désormais membre actif.")
                                ->success()
                                ->send();
                        }),

                    Action::make('exclude_athlete')
                        ->label('Non-admission ou exclusion (art. 27)')
                        ->icon(Heroicon::OutlinedXCircle)
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Prononcer la non-admission ou exclusion ?')
                        ->modalDescription('L\'athlète passera en statut inactif. Le motif sera consigné pour le Comité.')
                        ->visible(fn (Evaluation $record) => in_array($record->context, [EvaluationContext::Adaptation, EvaluationContext::EvaluationProbation, EvaluationContext::DisciplinaryProbation]))
                        ->action(function (Evaluation $record): void {
                            $record->athlete->update(['status' => AthleteStatus::Inactive]);
                            Notification::make()
                                ->title('Non-admission ou exclusion prononcée')
                                ->body("{$record->athlete->full_name} a été marqué comme inactif.")
                                ->danger()
                                ->send();
                        }),

                    EditAction::make(),
                    DeleteAction::make(),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->tooltip('Actions statutaires'),
            ], position: RecordActionsPosition::BeforeCells);
    }
}
