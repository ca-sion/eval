<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use App\Services\TiivaApiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListGroups extends ListRecords
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_tiiva_groups')
                ->label('Synchroniser avec Tiiva')
                ->icon(Heroicon::OutlinedCloudArrowDown)
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Synchroniser les groupes depuis Tiiva ?')
                ->modalDescription('Cette action met à jour les groupes d\'entraînement, leurs couleurs et leur ordre depuis Tiiva.')
                ->action(function (): void {
                    $apiService = app(TiivaApiService::class);
                    $result = $apiService->syncGroups();

                    Notification::make()
                        ->title('Groupes Tiiva synchronisés')
                        ->body("{$result['created']} créés, {$result['updated']} mis à jour ({$result['total']} groupes au total).")
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }
}
