<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    /**
     * Crée ou met à jour les comptes utilisateurs indispensables de base (installation / production).
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'technique@casion.ch'],
            [
                'name' => 'Chef technique CA Sion',
                'password' => 'password',
            ]
        );
    }
}
