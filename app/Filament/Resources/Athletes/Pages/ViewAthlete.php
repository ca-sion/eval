<?php

namespace App\Filament\Resources\Athletes\Pages;

use App\Filament\Resources\Athletes\AthleteResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAthlete extends ViewRecord
{
    protected static string $resource = AthleteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
