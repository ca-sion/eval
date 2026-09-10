<?php

namespace App\Filament\Resources\EvaluationSessions;

use App\Filament\Resources\EvaluationSessions\Pages\CreateEvaluationSession;
use App\Filament\Resources\EvaluationSessions\Pages\EditEvaluationSession;
use App\Filament\Resources\EvaluationSessions\Pages\ListEvaluationSessions;
use App\Filament\Resources\EvaluationSessions\RelationManagers\EvaluationsRelationManager;
use App\Models\EvaluationSession;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
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
                            ->placeholder('ex. Session d\'automne S35 - 2026')
                            ->columnSpanFull(),
                        DatePicker::make('start_date')
                            ->label('Date de début')
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Date de fin (échéance)')
                            ->required(),
                        TextInput::make('weeks_count')
                            ->label('Durée (nombre de semaines)')
                            ->numeric()
                            ->required()
                            ->default(5),
                        Toggle::make('is_closed')
                            ->label('Session clôturée')
                            ->helperText('Une fois clôturée, la saisie mobile pour cette session est définitivement verrouillée')
                            ->default(false),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Session')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('start_date')
                    ->label('Début')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Échéance')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('weeks_count')
                    ->label('Durée')
                    ->formatStateUsing(fn ($state) => $state.' sem.')
                    ->alignCenter(),
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
                //
            ])
            ->recordActions([
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
            'edit' => EditEvaluationSession::route('/{record}/edit'),
        ];
    }
}
