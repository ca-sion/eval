<?php

namespace App\Console\Commands;

use App\Models\EvaluationSession;
use App\Services\TiivaApiService;
use Illuminate\Console\Command;

class SyncTiivaCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiiva:sync {--session= : ID de la session d\'évaluation optionnelle à synchroniser}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronise les groupes d\'entraînement, athlètes et responsables légaux depuis l\'API REST Tiiva.';

    /**
     * Execute the console command.
     */
    public function handle(TiivaApiService $apiService): int
    {
        $this->info('Démarrage de la synchronisation avec Tiiva...');

        $sessionId = $this->option('session');
        $session = $sessionId ? EvaluationSession::find($sessionId) : null;

        $result = $apiService->syncAll($session);

        if (! ($result['success'] ?? false)) {
            $this->error('Erreur lors de la synchronisation Tiiva : '.($result['error'] ?? 'Inconnue'));

            return Command::FAILURE;
        }

        $this->info('Synchronisation Tiiva réussie !');
        $this->table(
            ['Élément', 'Nombre'],
            [
                ['Groupes synchronisés', $result['groups_synced']],
                ['Athlètes synchronisés', $result['athletes_synced']],
                ['Nouveaux athlètes créés (Adaptation)', $result['athletes_created']],
                ['Athlètes mis à jour', $result['athletes_updated']],
                ['Athlètes désactivés (plus dans un groupe actif)', $result['athletes_deactivated']],
                ['Évaluations créées', $result['evaluations_created']],
            ]
        );

        return Command::SUCCESS;
    }
}
