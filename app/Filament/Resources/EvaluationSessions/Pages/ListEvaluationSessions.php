<?php

namespace App\Filament\Resources\EvaluationSessions\Pages;

use App\Filament\Resources\EvaluationSessions\EvaluationSessionResource;
use App\Models\Evaluation;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;

class ListEvaluationSessions extends ListRecords
{
    protected static string $resource = EvaluationSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('cleanup_orphans')
                ->label(fn () => 'Nettoyer les évaluations orphelines ('.Evaluation::whereNull('evaluation_session_id')->count().')')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->visible(fn () => Evaluation::whereNull('evaluation_session_id')->exists())
                ->requiresConfirmation()
                ->modalHeading('Supprimer les évaluations orphelines ?')
                ->modalDescription('Ces fiches d\'évaluations ne sont rattachées à aucune session d\'évaluation existante. Cette action supprimera définitivement ces fiches orphelines de la base de données.')
                ->action(function (): void {
                    $count = Evaluation::whereNull('evaluation_session_id')->delete();

                    Notification::make()
                        ->title('Nettoyage effectué')
                        ->body("{$count} évaluation(s) orpheline(s) ont été supprimées.")
                        ->success()
                        ->send();
                }),
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
