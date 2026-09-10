<?php

namespace App\Filament\Resources\EvaluationSessions;

use App\Filament\Resources\EvaluationSessions\Pages\CreateEvaluationSession;
use App\Filament\Resources\EvaluationSessions\Pages\EditEvaluationSession;
use App\Filament\Resources\EvaluationSessions\Pages\ListEvaluationSessions;
use App\Filament\Resources\EvaluationSessions\Pages\ManageEvaluationSessionWorkflow;
use App\Filament\Resources\EvaluationSessions\RelationManagers\EvaluationsRelationManager;
use App\Models\EvaluationSession;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class EvaluationSessionResource extends Resource
{
    protected static ?string $model = EvaluationSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $modelLabel = 'Session d\'évaluation';

    protected static ?string $pluralModelLabel = 'Sessions d\'évaluation';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Paramètres de la session')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->label('Intitulé de la session')
                            ->required()
                            ->placeholder('ex. Session d\'automne')
                            ->columnSpanFull(),
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
                            ->required()
                            ->default(fn () => (int) config('evaluation.durations.collective_session_weeks', 5)),
                        CheckboxList::make('groups')
                            ->relationship('groups', 'name')
                            ->label('Groupes concernés')
                            ->helperText('Sélectionner les groupes ciblés par cette session. Laisser vide pour inclure tous les groupes.')
                            ->bulkToggleable()
                            ->columns(3)
                            ->columnSpanFull(),
                        Toggle::make('is_closed')
                            ->label('Session clôturée')
                            ->helperText('Une fois clôturée, la saisie mobile pour cette session est définitivement verrouillée')
                            ->default(false)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Session')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d.m.Y')
                    ->sortable(),
                TextColumn::make('submission_deadline')
                    ->label('Date limite')
                    ->date('d.m.Y')
                    ->placeholder('Fin de période')
                    ->sortable(),
                TextColumn::make('groups.name')
                    ->label('Groupes')
                    ->badge()
                    ->default('Tous les groupes')
                    ->limitList(2),
                IconColumn::make('is_closed')
                    ->label('Clôturée')
                    ->boolean()
                    ->alignCenter(),
                TextColumn::make('evaluations_count')
                    ->label('Évaluations')
                    ->counts('evaluations')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                TernaryFilter::make('is_closed')
                    ->label('État de la session')
                    ->placeholder('Toutes les sessions')
                    ->trueLabel('Sessions clôturées')
                    ->falseLabel('Sessions ouvertes'),
            ])
            ->recordActions([
                Action::make('workflow')
                    ->label('Piloter le workflow')
                    ->icon(Heroicon::OutlinedSparkles)
                    ->color('primary')
                    ->url(fn (EvaluationSession $record): string => static::getUrl('workflow', ['record' => $record])),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            EvaluationsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvaluationSessions::route('/'),
            'create' => CreateEvaluationSession::route('/create'),
            'workflow' => ManageEvaluationSessionWorkflow::route('/{record}/workflow'),
            'edit' => EditEvaluationSession::route('/{record}/edit'),
        ];
    }
}
