<?php

namespace App\Filament\Resources\Athletes;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Filament\Resources\Athletes\Pages\CreateAthlete;
use App\Filament\Resources\Athletes\Pages\EditAthlete;
use App\Filament\Resources\Athletes\Pages\ListAthletes;
use App\Filament\Resources\Athletes\Pages\ViewAthlete;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\Group;
use BackedEnum;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class AthleteResource extends Resource
{
    protected static ?string $model = Athlete::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $modelLabel = 'Athlète';

    protected static ?string $pluralModelLabel = 'Athlètes';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Identité de l\'athlète')
                    ->columns(2)
                    ->schema([
                        TextInput::make('last_name')
                            ->label('Nom')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('first_name')
                            ->label('Prénom')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('birth_year')
                            ->label('Année de naissance')
                            ->numeric()
                            ->required()
                            ->minValue(1980)
                            ->maxValue((int) date('Y')),
                        Select::make('group_id')
                            ->label('Groupe d\'entraînement')
                            ->relationship('group', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('status')
                            ->label('Statut')
                            ->options(AthleteStatus::class)
                            ->required()
                            ->default(AthleteStatus::Active),
                    ]),

                Section::make('Période d\'adaptation (Nouveaux arrivants)')
                    ->visibleOn('create')
                    ->schema([
                        Checkbox::make('start_adaptation')
                            ->label('Démarrer une période d\'adaptation de 5 semaines (Art. 3.4 & 10.2)')
                            ->helperText('Crée automatiquement une évaluation individuelle active de 5 semaines et place l\'athlète en statut adaptation')
                            ->default(false),
                    ]),

                Section::make('Identifiants externes')
                    ->columns(3)
                    ->schema([
                        TextInput::make('license_number')
                            ->label('N° Licence Swiss Athletics')
                            ->nullable(),
                        TextInput::make('tiiva_id')
                            ->label('Identifiant Tiiva')
                            ->nullable(),
                        TextInput::make('nds_number')
                            ->label('N° NDS Jeunesse+Sport')
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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
                    ->sortable()
                    ->alignCenter(),
                TextColumn::make('group.name')
                    ->label('Groupe')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->sortable(),
                TextColumn::make('license_number')
                    ->label('Licence')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tiiva_id')
                    ->label('ID Tiiva')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('nds_number')
                    ->label('N° NDS')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                Action::make('disciplinary_probation')
                    ->label('Sursis disciplinaire (Art. 27)')
                    ->icon(Heroicon::OutlinedExclamationTriangle)
                    ->color('danger')
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

                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('move_group')
                        ->label('Déplacer vers le groupe...')
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

                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informations de l\'athlète')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('full_name')->label('Athlète'),
                        TextEntry::make('birth_year')->label('Année de naissance'),
                        TextEntry::make('group.name')->label('Groupe actuel'),
                        TextEntry::make('status')->label('Statut')->badge(),
                        TextEntry::make('license_number')->label('Licence'),
                        TextEntry::make('nds_number')->label('N° NDS'),
                    ]),

                Section::make('Historique complet des évaluations et périodes probatoires')
                    ->schema([
                        RepeatableEntry::make('evaluations')
                            ->label('')
                            ->columns(5)
                            ->schema([
                                TextEntry::make('context')->label('Contexte')->badge(),
                                TextEntry::make('start_date')->label('Début')->date('d/m/Y'),
                                TextEntry::make('end_date')->label('Échéance')->date('d/m/Y'),
                                TextEntry::make('final_score')->label('Note finale')->formatStateUsing(fn ($state) => $state !== null ? number_format($state, 2).' / 10' : '-'),
                                TextEntry::make('decision')->label('Décision')->badge(),
                                TextEntry::make('coach_notes')->label('Remarques')->columnSpanFull(),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAthletes::route('/'),
            'create' => CreateAthlete::route('/create'),
            'view' => ViewAthlete::route('/{record}'),
            'edit' => EditAthlete::route('/{record}/edit'),
        ];
    }
}
