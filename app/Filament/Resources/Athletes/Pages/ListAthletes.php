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
