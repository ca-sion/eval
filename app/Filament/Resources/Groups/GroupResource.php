<?php

namespace App\Filament\Resources\Groups;

use App\Enums\ArbitrageMode;
use App\Filament\Resources\Groups\Pages\CreateGroup;
use App\Filament\Resources\Groups\Pages\EditGroup;
use App\Filament\Resources\Groups\Pages\ListGroups;
use App\Models\Group;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GroupResource extends Resource
{
    protected static ?string $model = Group::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'Groupe';

    protected static ?string $pluralModelLabel = 'Groupes d\'entraînement';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Informations du groupe')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Nom du groupe')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('Identifiant URL (slug)')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Généré automatiquement à la création'),
                        TextInput::make('tiiva_id')
                            ->label('Identifiant Tiiva')
                            ->nullable(),
                        TextInput::make('access_token')
                            ->label('Clé d\'accès mobile (Token)')
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Sécurise l\'accès mobile des entraîneurs sans mot de passe'),
                    ]),

                Section::make('Paramètres d\'arbitrage et de sélection')
                    ->columns(2)
                    ->schema([
                        Select::make('arbitration_mode')
                            ->label('Mode d\'arbitrage')
                            ->options(ArbitrageMode::class)
                            ->required()
                            ->default(ArbitrageMode::Quota),
                        TextInput::make('quota_places')
                            ->label('Nombre de places (Mode Quota)')
                            ->numeric()
                            ->default(12),
                        TextInput::make('min_score')
                            ->label('Note minimale de sélection (Mode Threshold)')
                            ->numeric()
                            ->step(0.01)
                            ->default(6.50),
                    ]),

                Section::make('Défauts d\'entraînement et bénévolat')
                    ->columns(2)
                    ->schema([
                        TextInput::make('default_sessions_per_week')
                            ->label('Séances par semaine par défaut')
                            ->numeric()
                            ->required()
                            ->default(3),
                        TextInput::make('default_competitions_planned')
                            ->label('Compétitions prévues par défaut')
                            ->numeric()
                            ->required()
                            ->default(6),
                        TextInput::make('max_volunteering_age')
                            ->label('Âge max. pour critère bénévolat (ans)')
                            ->numeric()
                            ->required()
                            ->default(14)
                            ->helperText('Au-delà de cet âge, le critère C9 est neutralisé'),
                        TextInput::make('required_volunteering_count')
                            ->label('Missions de bénévolat requises')
                            ->numeric()
                            ->required()
                            ->default(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Groupe')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('arbitration_mode')
                    ->label('Arbitrage')
                    ->badge(),
                TextColumn::make('quota_places')
                    ->label('Quota')
                    ->alignCenter(),
                TextColumn::make('min_score')
                    ->label('Seuil min.')
                    ->alignCenter()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2).' / 10' : '-'),
                TextColumn::make('default_sessions_per_week')
                    ->label('Séances/sem')
                    ->alignCenter(),
                TextColumn::make('default_competitions_planned')
                    ->label('Compétitions')
                    ->alignCenter(),
                TextColumn::make('athletes_count')
                    ->label('Athlètes')
                    ->counts('athletes')
                    ->alignCenter()
                    ->badge()
                    ->color('primary'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('copy_link')
                    ->label('Copier lien')
                    ->icon(Heroicon::OutlinedClipboardDocument)
                    ->color('gray')
                    ->action(function (Group $record): void {
                        Notification::make()
                            ->title('Lien mobile copié')
                            ->body($record->getMobileUrl())
                            ->success()
                            ->send();
                    }),

                Action::make('whatsapp')
                    ->label('WhatsApp')
                    ->icon(Heroicon::OutlinedChatBubbleOvalLeftEllipsis)
                    ->color('success')
                    ->url(fn (Group $record): string => $record->getWhatsAppShareUrl())
                    ->openUrlInNewTab(),

                Action::make('regenerate_token')
                    ->label('Régénérer token')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Régénérer le token d\'accès ?')
                    ->modalDescription('L\'ancien lien partagé aux entraîneurs deviendra immédiatement invalide.')
                    ->action(function (Group $record): void {
                        $record->regenerateAccessToken();
                        Notification::make()
                            ->title('Nouveau token généré avec succès')
                            ->success()
                            ->send();
                    }),

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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGroups::route('/'),
            'create' => CreateGroup::route('/create'),
            'edit' => EditGroup::route('/{record}/edit'),
        ];
    }
}
