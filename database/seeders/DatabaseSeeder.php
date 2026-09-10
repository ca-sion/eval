<?php

namespace Database\Seeders;

use App\Enums\ArbitrageMode;
use App\Enums\AthleteStatus;
use App\Enums\AthleticLevel;
use App\Enums\EvaluationContext;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use App\Models\User;
use App\Services\EvaluationCalculatorService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Administrateur Responsable Technique
        User::firstOrCreate(
            ['email' => 'admin@casion.ch'],
            [
                'name' => 'Responsable Technique CA Sion',
                'password' => Hash::make('password'),
            ]
        );

        // 2. Groupes d'entraînement
        $groupU16G = Group::create([
            'name' => 'U16 Garçons (Sprint / Sauts)',
            'slug' => 'u16-garcons',
            'arbitration_mode' => ArbitrageMode::Quota,
            'quota_places' => 3,
            'min_score' => 6.50,
            'default_sessions_per_week' => 3,
            'default_competitions_planned' => 6,
            'max_volunteering_age' => 14,
            'required_volunteering_count' => 2,
            'tiiva_id' => 'TIIVA-GRP-01',
        ]);

        $groupU16F = Group::create([
            'name' => 'U16 Filles (Demi-Fond / Haies)',
            'slug' => 'u16-filles',
            'arbitration_mode' => ArbitrageMode::Threshold,
            'quota_places' => 10,
            'min_score' => 7.00,
            'default_sessions_per_week' => 3,
            'default_competitions_planned' => 5,
            'max_volunteering_age' => 14,
            'required_volunteering_count' => 2,
            'tiiva_id' => 'TIIVA-GRP-02',
        ]);

        $groupU18 = Group::create([
            'name' => 'U18 / U20 Élite',
            'slug' => 'u18-u20-elite',
            'arbitration_mode' => ArbitrageMode::Quota,
            'quota_places' => 2,
            'min_score' => 7.50,
            'default_sessions_per_week' => 4,
            'default_competitions_planned' => 8,
            'max_volunteering_age' => 14,
            'required_volunteering_count' => 2,
            'tiiva_id' => 'TIIVA-GRP-03',
        ]);

        // 3. Athlètes
        $athletesU16G = [
            ['first_name' => 'Théo', 'last_name' => 'Bonvin', 'birth_year' => 2012, 'license' => 'SA-9012'],
            ['first_name' => 'Maxime', 'last_name' => 'Rey', 'birth_year' => 2012, 'license' => 'SA-9013'],
            ['first_name' => 'Noah', 'last_name' => 'Sierro', 'birth_year' => 2011, 'license' => 'SA-9014'],
            ['first_name' => 'Adrien', 'last_name' => 'Fournier', 'birth_year' => 2012, 'license' => 'SA-9015'],
        ];

        $createdU16G = [];
        foreach ($athletesU16G as $data) {
            $createdU16G[] = Athlete::create([
                'group_id' => $groupU16G->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_year' => $data['birth_year'],
                'license_number' => $data['license'],
                'status' => AthleteStatus::Active,
            ]);
        }

        $athletesU16F = [
            ['first_name' => 'Chloé', 'last_name' => 'Bagnoud', 'birth_year' => 2012, 'license' => 'SA-9021'],
            ['first_name' => 'Lola', 'last_name' => 'Constantin', 'birth_year' => 2011, 'license' => 'SA-9022'],
            ['first_name' => 'Julie', 'last_name' => 'Ritz', 'birth_year' => 2012, 'license' => 'SA-9023'],
        ];

        $createdU16F = [];
        foreach ($athletesU16F as $data) {
            $createdU16F[] = Athlete::create([
                'group_id' => $groupU16F->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_year' => $data['birth_year'],
                'license_number' => $data['license'],
                'status' => AthleteStatus::Active,
            ]);
        }

        // Athlète en période d'adaptation (nouveau arrivant)
        $newJoiner = Athlete::create([
            'group_id' => $groupU16G->id,
            'first_name' => 'Lucas',
            'last_name' => 'Clavien',
            'birth_year' => 2012,
            'status' => AthleteStatus::Adaptation,
        ]);

        Evaluation::create([
            'athlete_id' => $newJoiner->id,
            'group_id' => $groupU16G->id,
            'context' => EvaluationContext::Adaptation,
            'start_date' => Carbon::today()->subDays(10),
            'end_date' => Carbon::today()->addDays(25),
            'weeks_count' => 5,
            'sessions_per_week' => 3,
            'competitions_planned' => 6,
            'c4_commitment' => 8.0,
            'c5_behavior' => 9.0,
            'lateness_count' => 0,
        ]);

        // 4. Session collective générale
        $today = Carbon::today();
        $session = EvaluationSession::create([
            'title' => 'Session d\'automne S35 - 2026',
            'start_date' => $today->copy()->subDays(14),
            'end_date' => $today->copy()->addDays(21),
            'weeks_count' => 5,
            'is_closed' => false,
        ]);

        // 5. Création des évaluations pour les athlètes U16G
        $evalData = [
            [
                'real_attendances' => 15, 'competitions_done' => 6, 'parent_volunteering_count' => 3,
                'c4_commitment' => 9.5, 'c5_behavior' => 9.0, 'c6_level' => AthleticLevel::National,
                'c7_progress' => 9.0, 'c8_environment' => 8.5, 'has_club_engagement' => true,
            ],
            [
                'real_attendances' => 13, 'competitions_done' => 5, 'parent_volunteering_count' => 2,
                'c4_commitment' => 8.0, 'c5_behavior' => 8.5, 'c6_level' => AthleticLevel::Regional,
                'c7_progress' => 8.0, 'c8_environment' => 7.5, 'has_club_engagement' => false,
            ],
            [
                'real_attendances' => 10, 'competitions_done' => 3, 'parent_volunteering_count' => 1,
                'c4_commitment' => 6.5, 'c5_behavior' => 6.0, 'c6_level' => AthleticLevel::Cantonal,
                'c7_progress' => 6.5, 'c8_environment' => 6.0, 'lateness_count' => 2,
            ],
            [
                'is_injured' => true, 'real_attendances' => 5, 'competitions_done' => 1, 'parent_volunteering_count' => 2,
                'c4_commitment' => 7.0, 'c5_behavior' => 7.5, 'c6_level' => AthleticLevel::Cantonal,
                'c7_progress' => 6.0, 'c8_environment' => 6.5,
            ],
        ];

        foreach ($createdU16G as $idx => $athlete) {
            $data = $evalData[$idx];
            Evaluation::create(array_merge([
                'athlete_id' => $athlete->id,
                'group_id' => $groupU16G->id,
                'evaluation_session_id' => $session->id,
                'context' => EvaluationContext::Collective,
                'start_date' => $session->start_date,
                'end_date' => $session->end_date,
                'weeks_count' => $session->weeks_count,
                'sessions_per_week' => 3,
                'competitions_planned' => 6,
            ], $data));
        }

        // Évaluations U16F
        foreach ($createdU16F as $idx => $athlete) {
            Evaluation::create([
                'athlete_id' => $athlete->id,
                'group_id' => $groupU16F->id,
                'evaluation_session_id' => $session->id,
                'context' => EvaluationContext::Collective,
                'start_date' => $session->start_date,
                'end_date' => $session->end_date,
                'weeks_count' => $session->weeks_count,
                'sessions_per_week' => 3,
                'competitions_planned' => 5,
                'real_attendances' => 14 - ($idx * 2),
                'competitions_done' => 5 - $idx,
                'parent_volunteering_count' => 2,
                'c4_commitment' => 8.5 - $idx,
                'c5_behavior' => 9.0 - ($idx * 0.5),
                'c6_level' => AthleticLevel::Regional,
                'c7_progress' => 8.0 - $idx,
                'c8_environment' => 8.0,
            ]);
        }

        // 6. Arbitrage initial pour tous les groupes
        $calculator = app(EvaluationCalculatorService::class);
        $calculator->arbitrateGroup($groupU16G, $session);
        $calculator->arbitrateGroup($groupU16F, $session);
    }
}
