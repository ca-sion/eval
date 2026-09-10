<?php

namespace App\Filament\Resources\EvaluationSessions\RelationManagers;

use App\Enums\AthleteStatus;
use App\Enums\AthleticLevel;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationCriterion;
use App\Enums\EvaluationDecision;
use App\Models\Evaluation;
use App\Models\Group;
use App\Services\EvaluationCalculatorService;
use App\Services\PdfReportService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ColumnGroup;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\TextInputColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class EvaluationsRelationManager extends RelationManager
{
    protected static string $relationship = 'evaluations';

    protected static ?string $title = 'Évaluations et arbitrage des athlètes';

    /**
     * Génère l'infobulle détaillée et officielle pour un critère d'évaluation.
     */
    protected static function criterionTooltip(EvaluationCriterion $criterion): string
    {
        $percentage = (int) ($criterion->defaultWeight() * 100);

        return "{$criterion->code()} : {$criterion->getLabel()} ({$percentage}%)\n\n{$criterion->getDescription()}";
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Assiduité et compétitions')
                    ->columns(3)
                    ->schema([
                        TextInput::make('sessions_per_week')
                            ->numeric()
                            ->required()
                            ->label('Séances / semaine'),
                        TextInput::make('real_attendances')
                            ->numeric()
                            ->label('Présences réelles (NDS)')
                            ->helperText(EvaluationCriterion::C1_Attendance->getDescription()),
                        TextInput::make('lateness_count')
                            ->numeric()
                            ->label('Nombre de retards')
                            ->helperText(EvaluationCriterion::C2_Punctuality->getDescription()),
                        Toggle::make('is_injured')
                            ->label('Blessure majeure')
                            ->inline(false),
                        TextInput::make('competitions_planned')
                            ->numeric()
                            ->required()
                            ->label('Compétitions prévues'),
                        TextInput::make('competitions_done')
                            ->numeric()
                            ->label('Compétitions faites')
                            ->helperText(EvaluationCriterion::C3_Competitions->getDescription()),
                        TextInput::make('parent_volunteering_count')
                            ->numeric()
                            ->label('Bénévolats parents faits')
                            ->helperText(EvaluationCriterion::C9_Volunteering->getDescription()),
                        Toggle::make('has_club_engagement')
                            ->label('Engagement club')
                            ->inline(false),
                    ]),

                Section::make('Évaluations qualitatives et niveau')
                    ->columns(3)
                    ->schema([
                        TextInput::make('c4_commitment')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->label(EvaluationCriterion::C4_Commitment->code().' : '.EvaluationCriterion::C4_Commitment->shortLabel())
                            ->hint('Pond. '.(int) (EvaluationCriterion::C4_Commitment->defaultWeight() * 100).'%')
                            ->helperText(EvaluationCriterion::C4_Commitment->getDescription()),

                        TextInput::make('c5_behavior')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->label(EvaluationCriterion::C5_Behavior->code().' : '.EvaluationCriterion::C5_Behavior->shortLabel())
                            ->hint('Pond. '.(int) (EvaluationCriterion::C5_Behavior->defaultWeight() * 100).'%')
                            ->helperText(EvaluationCriterion::C5_Behavior->getDescription()),

                        Select::make('c6_level')
                            ->options(AthleticLevel::class)
                            ->label(EvaluationCriterion::C6_Performance->code().' : '.EvaluationCriterion::C6_Performance->shortLabel())
                            ->hint('Pond. '.(int) (EvaluationCriterion::C6_Performance->defaultWeight() * 100).'%')
                            ->helperText(EvaluationCriterion::C6_Performance->getDescription()),

                        TextInput::make('c7_progress')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->label(EvaluationCriterion::C7_Progress->code().' : '.EvaluationCriterion::C7_Progress->shortLabel())
                            ->hint('Pond. '.(int) (EvaluationCriterion::C7_Progress->defaultWeight() * 100).'%')
                            ->helperText(EvaluationCriterion::C7_Progress->getDescription()),

                        TextInput::make('c8_environment')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.5)
                            ->label(EvaluationCriterion::C8_Environment->code().' : '.EvaluationCriterion::C8_Environment->shortLabel())
                            ->hint('Pond. '.(int) (EvaluationCriterion::C8_Environment->defaultWeight() * 100).'%')
                            ->helperText(EvaluationCriterion::C8_Environment->getDescription()),
                    ]),

                Section::make('Remarques et décision')
                    ->columns(2)
                    ->schema([
                        Select::make('decision')
                            ->options(EvaluationDecision::class)
                            ->label('Décision'),
                        Textarea::make('coach_notes')
                            ->label('Notes de l\'entraîneur')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('athlete.last_name')
            ->defaultGroup('group.name')
            ->defaultSort('rank', 'asc')
            ->striped()
            ->columns([
                // 1. Identité de l'athlète
                ColumnGroup::make('Identité de l\'athlète', [
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
                        ->alignCenter()
                        ->sortable(),
                ]),

                // 2. Assiduité et santé
                ColumnGroup::make('Assiduité et santé', [
                    TextInputColumn::make('sessions_per_week')
                        ->label('Séances/sem.')
                        ->tooltip('Nombre de séances d\'entraînement prévues par semaine')
                        ->alignCenter()
                        ->rules(['required', 'numeric', 'min:1'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextInputColumn::make('real_attendances')
                        ->label('Présences')
                        ->tooltip(EvaluationCriterion::C1_Attendance->getDescription())
                        ->alignCenter()
                        ->rules(['nullable', 'numeric', 'min:0'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextInputColumn::make('lateness_count')
                        ->label('Retards')
                        ->tooltip(EvaluationCriterion::C2_Punctuality->getDescription())
                        ->alignCenter()
                        ->rules(['numeric', 'min:0'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    ToggleColumn::make('is_injured')
                        ->label('Blessure')
                        ->tooltip('Blessure majeure : neutralise automatiquement C1 (Assiduité) et C3 (Compétitions)')
                        ->alignCenter()
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),
                ]),

                // 3. Compétitions et bénévolat
                ColumnGroup::make('Compétitions et bénévolat', [
                    TextInputColumn::make('competitions_planned')
                        ->label('Compét. prévues')
                        ->tooltip('Nombre de compétitions officielles ciblées sur la période')
                        ->alignCenter()
                        ->rules(['required', 'numeric', 'min:0'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextInputColumn::make('competitions_done')
                        ->label('Compét. faites')
                        ->tooltip(EvaluationCriterion::C3_Competitions->getDescription())
                        ->alignCenter()
                        ->rules(['numeric', 'min:0'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextInputColumn::make('parent_volunteering_count')
                        ->label('Bénévolats parents')
                        ->tooltip(EvaluationCriterion::C9_Volunteering->getDescription())
                        ->alignCenter()
                        ->rules(['numeric', 'min:0'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),
                ]),

                // 4. Critères d'évaluation (sur 10)
                ColumnGroup::make('Critères', [
                    TextColumn::make('c1_score')
                        ->label(EvaluationCriterion::C1_Attendance->code().' : '.EvaluationCriterion::C1_Attendance->shortLabel())
                        ->tooltip(fn (Evaluation $record) => $record->is_injured ? 'Critère neutralisé pour cause de blessure.' : static::criterionTooltip(EvaluationCriterion::C1_Attendance))
                        ->alignCenter()
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state, Evaluation $record) => $record->is_injured ? 'Neutralisé' : ($state !== null ? number_format($state, 1) : '-')),

                    TextColumn::make('c2_score')
                        ->label(EvaluationCriterion::C2_Punctuality->code().' : '.EvaluationCriterion::C2_Punctuality->shortLabel())
                        ->tooltip(static::criterionTooltip(EvaluationCriterion::C2_Punctuality))
                        ->alignCenter()
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 1) : '-'),

                    TextColumn::make('c3_score')
                        ->label(EvaluationCriterion::C3_Competitions->code().' : '.EvaluationCriterion::C3_Competitions->shortLabel())
                        ->tooltip(fn (Evaluation $record) => $record->is_injured ? 'Critère neutralisé pour cause de blessure.' : static::criterionTooltip(EvaluationCriterion::C3_Competitions))
                        ->alignCenter()
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state, Evaluation $record) => $record->is_injured ? 'Neutralisé' : ($state !== null ? number_format($state, 1) : '-')),

                    TextInputColumn::make('c4_commitment')
                        ->label(EvaluationCriterion::C4_Commitment->code().' : '.EvaluationCriterion::C4_Commitment->shortLabel())
                        ->tooltip(static::criterionTooltip(EvaluationCriterion::C4_Commitment))
                        ->alignCenter()
                        ->rules(['nullable', 'numeric', 'min:0', 'max:10'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextInputColumn::make('c5_behavior')
                        ->label(EvaluationCriterion::C5_Behavior->code().' : '.EvaluationCriterion::C5_Behavior->shortLabel())
                        ->tooltip(static::criterionTooltip(EvaluationCriterion::C5_Behavior))
                        ->alignCenter()
                        ->rules(['nullable', 'numeric', 'min:0', 'max:10'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    SelectColumn::make('c6_level')
                        ->label(EvaluationCriterion::C6_Performance->code().' : '.EvaluationCriterion::C6_Performance->shortLabel())
                        ->tooltip(static::criterionTooltip(EvaluationCriterion::C6_Performance))
                        ->options(AthleticLevel::class)
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextInputColumn::make('c7_progress')
                        ->label(EvaluationCriterion::C7_Progress->code().' : '.EvaluationCriterion::C7_Progress->shortLabel())
                        ->tooltip(static::criterionTooltip(EvaluationCriterion::C7_Progress))
                        ->alignCenter()
                        ->rules(['nullable', 'numeric', 'min:0', 'max:10'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextInputColumn::make('c8_environment')
                        ->label(EvaluationCriterion::C8_Environment->code().' : '.EvaluationCriterion::C8_Environment->shortLabel())
                        ->tooltip(static::criterionTooltip(EvaluationCriterion::C8_Environment))
                        ->alignCenter()
                        ->rules(['nullable', 'numeric', 'min:0', 'max:10'])
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextColumn::make('c9_score')
                        ->label(EvaluationCriterion::C9_Volunteering->code().' : '.EvaluationCriterion::C9_Volunteering->shortLabel())
                        ->tooltip(static::criterionTooltip(EvaluationCriterion::C9_Volunteering))
                        ->alignCenter()
                        ->placeholder('-')
                        ->formatStateUsing(fn ($state, Evaluation $record) => ! EvaluationCriterion::C9_Volunteering->isApplicable($record) ? 'N/A' : ($state !== null ? number_format($state, 1) : '-')),
                ]),

                // 5. Notes et bonus
                ColumnGroup::make('Notes et bonus', [
                    TextColumn::make('base_average')
                        ->label('Moyenne')
                        ->tooltip('Moyenne pondérée des critères notés sur 10')
                        ->alignCenter()
                        ->weight('bold')
                        ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) : '-'),

                    ToggleColumn::make('has_club_engagement')
                        ->label('Engagement club')
                        ->tooltip('Bonus club : +0.75 pt accordé pour engagement actif au club')
                        ->alignCenter()
                        ->afterStateUpdated(function (Evaluation $record): void {
                            app(EvaluationCalculatorService::class)->calculateAthlete($record);
                        }),

                    TextColumn::make('final_score')
                        ->label('Note')
                        ->tooltip('Note finale sur 10 (moyenne pondérée + bonus club)')
                        ->alignCenter()
                        ->badge()
                        ->color(fn ($state, Evaluation $record) => $state !== null && (float) $state >= (float) ($record->group?->min_score ?? 6.5) ? 'success' : 'danger')
                        ->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2) : '-')
                        ->weight('bold'),

                    TextColumn::make('rank')
                        ->label('Rang')
                        ->tooltip('Classement au sein du groupe d\'entraînement')
                        ->alignCenter()
                        ->badge()
                        ->color('info')
                        ->sortable(),
                ]),

                // 6. Synthèse et décision
                ColumnGroup::make('Synthèse et décision', [
                    TextColumn::make('decision')
                        ->label('Statut')
                        ->tooltip('Décision d\'admission ou d\'arbitrage')
                        ->badge(),

                    TextColumn::make('athlete.full_name')
                        ->label('Athlète')
                        ->toggleable(isToggledHiddenByDefault: true),
                ]),
            ])
            ->recordActions([
                ActionGroup::make([
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
                        ->label('Non-admission / exclusion (Art. 27)')
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
                    ->tooltip('Actions'),
            ], position: RecordActionsPosition::BeforeCells)
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
