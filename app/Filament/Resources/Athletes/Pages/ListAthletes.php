<?php

namespace App\Filament\Resources\Athletes\Pages;

use App\Enums\AthleteStatus;
use App\Filament\Resources\Athletes\AthleteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListAthletes extends ListRecords
{
    protected static string $resource = AthleteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Tous'),
            'active' => Tab::make('Actifs')
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Active)),
            'adaptation' => Tab::make('En adaptation')
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Adaptation)),
            'probation' => Tab::make('En sursis')
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Probation)),
            'inactive' => Tab::make('Inactifs')
                ->modifyQueryUsing(fn ($query) => $query->where('status', AthleteStatus::Inactive)),
        ];
    }
}
