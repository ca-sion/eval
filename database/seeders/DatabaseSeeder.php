<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Initialisation des comptes indispensables (toujours exécuté)
        $this->call(AdminUserSeeder::class);

        // 2. Données de démonstration et de test (uniquement en environnement local)
        if (app()->environment('local', 'testing')) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
