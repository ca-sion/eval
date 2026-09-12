<?php

namespace App\Filament\Resources\Athletes\Pages;

use App\Enums\AthleteStatus;
use App\Filament\Resources\Athletes\AthleteResource;
use App\Models\Group;
use App\Services\TiivaApiService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListAthletes extends ListRecords
{
    protected static string $resource = AthleteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('sync_tiiva')
                ->label('Synchroniser avec Tiiva')
                ->icon(Heroicon::OutlinedCloudArrowDown)
                ->color('primary')
                ->modalHeading('Synchroniser les athlètes depuis Tiiva')
                ->modalDescription('Sélectionnez les groupes d\'entraînement à synchroniser depuis Tiiva. Seuls les athlètes actifs de ces groupes seront importés.')
                ->form(function (): array {
                    $apiService = app(TiivaApiService::class);

                    // Charger d'abord les groupes depuis Tiiva si aucun n'est encore lié
                    if (Group::whereNotNull('tiiva_id')->count() === 0) {
                        try {
                            $apiService->syncGroups();
                        } catch (\Throwable) {
                            // ignore si non joignable
                        }
                    }

                    $groups = Group::where('is_activity_group', true)->pluck('name', 'id')->toArray();

                    if (empty($groups)) {
                        return [
                            TextEntry::make('info')
                                ->label('Synchronisation globale')
                                ->state('Tous les groupes et athlètes actifs vont être synchronisés depuis Tiiva.'),
                        ];
                    }

                    return [
                        CheckboxList::make('group_ids')
                            ->label('Groupes d\'entraînement à synchroniser')
                            ->options($groups)
                            ->default(array_keys($groups))
                            ->required()
                            ->columns(2)
                            ->helperText('Décochez les groupes que vous ne souhaitez pas synchroniser lors de cette opération.'),
                    ];
                })
                ->action(function (array $data): void {
                    $apiService = app(TiivaApiService::class);

                    if (empty($data['group_ids'])) {
                        $result = $apiService->syncAll();
                        $msg = "{$result['groups_synced']} groupes et {$result['athletes_synced']} athlètes synchronisés.";
                    } else {
                        $selectedGroups = Group::whereIn('id', $data['group_ids'])->get();
                        $totalSynced = 0;
                        $totalCreated = 0;
                        $totalUpdated = 0;

                        foreach ($selectedGroups as $group) {
                            if (empty($group->tiiva_id)) {
                                $apiService->syncGroups();
                                $group->refresh();
                            }

                            $res = $apiService->syncContacts($group);
                            $totalSynced += $res['total'];
                            $totalCreated += $res['created'];
                            $totalUpdated += $res['updated'];
                        }

                        $msg = "{$totalSynced} athlètes synchronisés sur ".count($selectedGroups)." groupe(s) ({$totalCreated} nouveaux arrivants en adaptation, {$totalUpdated} mis à jour).";
                    }

                    Notification::make()
                        ->title('Synchronisation Tiiva terminée')
                        ->body($msg)
                        ->success()
                        ->send();
                }),

            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'active' => Tab::make(AthleteStatus::Active->getLabel())
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Active)),
            'adaptation' => Tab::make(AthleteStatus::Adaptation->getLabel())
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Adaptation)),
            'probation' => Tab::make(AthleteStatus::Probation->getLabel())
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Probation)),
            'inactive' => Tab::make(AthleteStatus::Inactive->getLabel())
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Inactive)),
        ];
    }
}
