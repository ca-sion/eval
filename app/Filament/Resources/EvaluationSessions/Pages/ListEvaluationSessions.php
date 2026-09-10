<?php

namespace App\Filament\Resources\EvaluationSessions\Pages;

use App\Filament\Resources\EvaluationSessions\EvaluationSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;

class ListEvaluationSessions extends ListRecords
{
    protected static string $resource = EvaluationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Toutes'),
            'open' => Tab::make('Sessions ouvertes')
                ->modifyQueryUsing(fn ($query) => $query->where('is_closed', false)),
            'closed' => Tab::make('Sessions clôturées')
                ->modifyQueryUsing(fn ($query) => $query->where('is_closed', true)),
        ];
    }
}
