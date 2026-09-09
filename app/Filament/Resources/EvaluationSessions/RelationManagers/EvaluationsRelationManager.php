<?php

namespace App\Filament\Resources\EvaluationSessions\RelationManagers;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationDecision;
use App\Models\Evaluation;
use App\Models\Group;
use App\Services\EvaluationCalculatorService;
use App\Services\PdfReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluations';

    protected static ?string $title = 'Évaluations et Arbitrage des Athlètes';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('sessions_per_week')->numeric()->required()->label('Séances / semaine'),
                TextInput::make('competitions_planned')->numeric()->required()->label('Compétitions prévues'),
                TextInput::make('competitions_done')->numeric()->label('Compétitions faites'),
                TextInput::make('real_attendances')->numeric()->label('Présences réelles (NDS)'),
                TextInput::make('parent_volunteering_count')->numeric()->label('Bénévolat parents'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('athlete.last_name')
            ->defaultGroup('group.name')
            ->defaultSort('rank', 'asc')
            ->columns([
                TextColumn::make('athlete.last_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('athlete.first_name')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('athlete.birth_year')
                    ->label('Année')
                    ->alignCenter(),

                // Live Inline Editing
                TextInputColumn::make('real_attendances')
                    ->label('Présences NDS (C1)')
                    ->alignCenter()
                    ->rules(['nullable', 'numeric', 'min:0'])
                    ->afterStateUpdated(function (Evaluation $record): void {
                        app(EvaluationCalculatorService::class)->calculateAthlete($record);
                    }),

                TextInputColumn::make('competitions_done')
                    ->label('Compét. (C3)')
                    ->alignCenter()
                    ->rules(['numeric', 'min:0'])
                    ->afterStateUpdated(function (Evaluation $record): void {
                        app(EvaluationCalculatorService::class)->calculateAthlete($record);
                    }),

                TextInputColumn::make('parent_volunteering_count')
                    ->label('Bénévolat (C9)')
                    ->alignCenter()
                    ->rules(['numeric', 'min:0'])
                    ->afterStateUpdated(function (Evaluation $record): void {
                        app(EvaluationCalculatorService::class)->calculateAthlete($record);
                    }),

                ToggleColumn::make('is_injured')
                    ->label('Blessé')
                    ->afterStateUpdated(function (Evaluation $record): void {
                        app(EvaluationCalculatorService::class)->calculateAthlete($record);
                    }),

                ToggleColumn::make('has_club_engagement')
                    ->label('Bonus (+0.75)')
                    ->afterStateUpdated(function (Evaluation $record): void {
                        app(EvaluationCalculatorService::class)->calculateAthlete($record);
                    }),

                // Colonnes de scores calculés
                TextColumn::make('base_average')
                    ->label('Moyenne')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) : '-'),

                TextColumn::make('final_score')
                    ->label('Note finale')
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state, Evaluation $record) => $state !== null && (float) $state >= (float) ($record->group?->min_score ?? 6.5) ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).' / 10' : '-'),

                TextColumn::make('rank')
                    ->label('Rang')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->sortable(),

                TextColumn::make('decision')
                    ->label('Décision')
                    ->badge(),
            ])
            ->recordActions([
                Action::make('probation')
                    ->label('Sursis probatoire (Art. 10.5)')
                    ->icon(Heroicon::OutlinedClock)
                    ->color('warning')
                    ->visible(fn (Evaluation $record) => $record->decision === EvaluationDecision::ProbationNeeded || ($record->final_score !== null && $record->final_score < 6.5))
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Date de début du sursis')
                            ->default(Carbon::tomorrow())
                            ->required(),
                    ])
                    ->action(function (Evaluation $record, array $data): void {
                        $startDate = Carbon::parse($data['start_date']);

                        Evaluation::create([
                            'athlete_id' => $record->athlete_id,
                            'group_id' => $record->group_id,
                            'parent_evaluation_id' => $record->id,
                            'context' => EvaluationContext::EvaluationProbation,
                            'start_date' => $startDate,
                            'end_date' => $startDate->copy()->addDays(14),
                            'weeks_count' => 2,
                            'sessions_per_week' => $record->sessions_per_week,
                            'competitions_planned' => $record->competitions_planned,
                        ]);

                        $record->athlete->update(['status' => AthleteStatus::Probation]);

                        Notification::make()
                            ->title('Sursis probatoire de 2 semaines enclenché')
                            ->body("Période d'observation jusqu'au ".$startDate->copy()->addDays(14)->format('d/m/Y').'.')
                            ->success()
                            ->send();
                    }),

                Action::make('interview_pdf')
                    ->label('Fiche d\'entretien (PDF)')
                    ->icon(Heroicon::OutlinedDocumentArrowDown)
                    ->color('gray')
                    ->action(fn (Evaluation $record) => app(PdfReportService::class)->generateInterviewReport($record)),

                Action::make('validate_athlete')
                    ->label('Valider définitivement (Art. 10)')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
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
                    ->label('Non-admission / Exclusion (Art. 27)')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Prononcer la non-admission ou exclusion ?')
                    ->modalDescription('L\'athlète passera en statut inactif. Le motif sera consigné pour le Comité.')
                    ->visible(fn (Evaluation $record) => in_array($record->context, [EvaluationContext::Adaptation, EvaluationContext::EvaluationProbation, EvaluationContext::DisciplinaryProbation]))
                    ->action(function (Evaluation $record): void {
                        $record->athlete->update(['status' => AthleteStatus::Inactive]);
                        Notification::make()
                            ->title('Non-admission / exclusion prononcée')
                            ->body("{$record->athlete->full_name} a été marqué comme inactif.")
                            ->danger()
                            ->send();
                    }),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('recalculate_arbitrate')
                        ->label('Recalculer et arbitrer la sélection')
                        ->icon(Heroicon::OutlinedScale)
                        ->color('primary')
                        ->action(function (Collection $records): void {
                            $calculator = app(EvaluationCalculatorService::class);
                            $byGroup = $records->groupBy('group_id');

                            foreach ($byGroup as $groupId => $evals) {
                                $group = Group::find($groupId);
                                if ($group) {
                                    $calculator->arbitrateGroup($group, evaluations: $evals);
                                }
                            }

                            Notification::make()
                                ->title('Arbitrage recalculé avec succès')
                                ->body("Les rangs et décisions ont été mis à jour pour {$records->count()} athlètes.")
                                ->success()
                                ->send();
                        }),

                    BulkAction::make('set_sessions')
                        ->label('Définir séances / semaine')
                        ->form([
                            TextInput::make('sessions_per_week')->numeric()->required()->label('Séances par semaine'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $calculator = app(EvaluationCalculatorService::class);
                            foreach ($records as $record) {
                                $record->sessions_per_week = (int) $data['sessions_per_week'];
                                $record->save();
                                $calculator->calculateAthlete($record);
                            }

                            Notification::make()->title('Séances par semaine mises à jour')->success()->send();
                        }),

                    BulkAction::make('set_competitions')
                        ->label('Définir compétitions prévues')
                        ->form([
                            TextInput::make('competitions_planned')->numeric()->required()->label('Compétitions prévues'),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $calculator = app(EvaluationCalculatorService::class);
                            foreach ($records as $record) {
                                $record->competitions_planned = (int) $data['competitions_planned'];
                                $record->save();
                                $calculator->calculateAthlete($record);
                            }

                            Notification::make()->title('Compétitions prévues mises à jour')->success()->send();
                        }),

                    BulkAction::make('toggle_club_bonus')
                        ->label('Attribuer le bonus club (+0.75)')
                        ->action(function (Collection $records): void {
                            $calculator = app(EvaluationCalculatorService::class);
                            foreach ($records as $record) {
                                $record->has_club_engagement = true;
                                $record->save();
                                $calculator->calculateAthlete($record);
                            }

                            Notification::make()->title('Bonus club attribué aux athlètes sélectionnés')->success()->send();
                        }),

                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
