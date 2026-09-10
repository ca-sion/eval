<?php

namespace App\Filament\Resources\Groups\RelationManagers;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\Group;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AthletesRelationManager extends RelationManager
{
    protected static string $relationship = 'athletes';

    protected static ?string $title = 'Athlètes membres du groupe';

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            // Form handled by main athlete editor
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('last_name')
            ->defaultSort('last_name', 'asc')
            ->columns([
                TextColumn::make('last_name')
                    ->label('Nom')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('first_name')
                    ->label('Prénom')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('birth_year')
                    ->label('Année')
                    ->alignCenter()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),

                TextColumn::make('license_number')
                    ->label('Licence')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('nds_number')
                    ->label('N° NDS')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Statut')
                    ->options(AthleteStatus::class),
            ])
            ->recordActions([
                ActionGroup::make([
                    Action::make('disciplinary_probation')
                        ->label('Sursis disciplinaire (art. 27)')
                        ->icon(Heroicon::OutlinedExclamationTriangle)
                        ->color('gray')
                        ->form([
                            Textarea::make('reason')
                                ->label('Motif de l\'avertissement disciplinaire')
                                ->required()
                                ->rows(3),
                            DatePicker::make('start_date')
                                ->label('Date de déclenchement')
                                ->required()
                                ->default(Carbon::tomorrow()),
                        ])
                        ->action(function (Athlete $record, array $data): void {
                            $group = $record->group;
                            $startDate = Carbon::parse($data['start_date']);

                            Evaluation::create([
                                'athlete_id' => $record->id,
                                'group_id' => $record->group_id,
                                'context' => EvaluationContext::DisciplinaryProbation,
                                'start_date' => $startDate,
                                'end_date' => $startDate->copy()->addDays(14),
                                'weeks_count' => 2,
                                'sessions_per_week' => $group?->default_sessions_per_week ?? 3,
                                'competitions_planned' => $group?->default_competitions_planned ?? 6,
                                'coach_notes' => $data['reason'],
                            ]);

                            $record->update(['status' => AthleteStatus::Probation]);

                            Notification::make()
                                ->title('Sursis disciplinaire déclenché (2 semaines)')
                                ->body("Période d'observation enregistrée pour {$record->full_name}.")
                                ->warning()
                                ->send();
                        }),

                    Action::make('validate_athlete')
                        ->label('Valider définitivement (art. 10)')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('gray')
                        ->visible(fn (Athlete $record) => in_array($record->status, [AthleteStatus::Adaptation, AthleteStatus::Probation]))
                        ->requiresConfirmation()
                        ->modalHeading('Valider l\'admission définitive ?')
                        ->action(function (Athlete $record): void {
                            $record->update(['status' => AthleteStatus::Active]);
                            Notification::make()
                                ->title('Athlète validé définitivement')
                                ->body("{$record->full_name} est désormais membre actif.")
                                ->success()
                                ->send();
                        }),

                    ViewAction::make(),
                    EditAction::make(),
                ])
                    ->icon(Heroicon::EllipsisVertical)
                    ->tooltip('Actions'),
            ], position: RecordActionsPosition::BeforeCells)
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('move_group')
                        ->label('Changer de groupe d\'entraînement')
                        ->icon(Heroicon::OutlinedArrowRightOnRectangle)
                        ->form([
                            Select::make('target_group_id')
                                ->label('Nouveau groupe d\'affectation')
                                ->options(Group::pluck('name', 'id'))
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $targetGroup = Group::find($data['target_group_id']);
                            foreach ($records as $record) {
                                $record->update(['group_id' => $targetGroup->id]);
                            }

                            Notification::make()
                                ->title('Athlètes déplacés avec succès')
                                ->body("{$records->count()} athlètes ont été affectés au groupe {$targetGroup->name}.")
                                ->success()
                                ->send();
                        }),
                ]),
            ]);
    }
}
