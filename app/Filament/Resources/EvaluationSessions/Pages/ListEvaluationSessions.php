<?php

namespace App\Filament\Resources\EvaluationSessions\Pages;

use App\Filament\Resources\EvaluationSessions\EvaluationSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListEvaluationSessions extends ListRecords
{
    protected static string $resource = EvaluationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
