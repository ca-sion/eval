<?php

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationDecision;
use App\Filament\Resources\EvaluationSessions\Pages\ManageEvaluationSessionWorkflow;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use App\Models\User;
use App\Services\EvaluationCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('admin can access the evaluation session workflow page', function () {
    $admin = User::factory()->create();
    $session = EvaluationSession::create([
        'title' => 'Session Automne 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    $this->actingAs($admin)
        ->get("/admin/evaluation-sessions/{$session->id}/workflow")
        ->assertStatus(200)
        ->assertSee('Session Automne 2026')
        ->assertSee('Étape 1 : Initialisation et effectifs')
        ->assertSee('Étape 2 : Saisie mobile des entraîneurs')
        ->assertSee('Étape 3 : Présences NDS et bénévolats')
        ->assertSee('Étape 4 : Arbitrage et sélection')
        ->assertSee('Étape 5 : Livrables officiels et clôture');
});

test('session progress stats are correctly calculated by EvaluationCalculatorService', function () {
    $group = Group::create([
        'name' => 'Groupe Test U16',
        'default_sessions_per_week' => 3,
        'default_competitions_planned' => 6,
    ]);

    $athlete1 = Athlete::create([
        'first_name' => 'Alice',
        'last_name' => 'Durand',
        'birth_year' => 2012,
        'group_id' => $group->id,
        'status' => AthleteStatus::Active,
    ]);

    $athlete2 = Athlete::create([
        'first_name' => 'Bob',
        'last_name' => 'Martin',
        'birth_year' => 2011,
        'group_id' => $group->id,
        'status' => AthleteStatus::Active,
    ]);

    $session = EvaluationSession::create([
        'title' => 'Session S35',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    Evaluation::create([
        'athlete_id' => $athlete1->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'weeks_count' => 5,
        'c4_commitment' => 8.0,
        'c5_behavior' => 9.0,
        'c6_level' => 'cantonal',
        'c7_progress' => 8.0,
        'c8_environment' => 8.5,
        'real_attendances' => 14,
        'final_score' => 8.2,
        'decision' => EvaluationDecision::Retained,
    ]);

    Evaluation::create([
        'athlete_id' => $athlete2->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'weeks_count' => 5,
    ]);

    $calculator = app(EvaluationCalculatorService::class);
    $stats = $calculator->getSessionProgressStats($session);

    expect($stats['total_active_athletes'])->toBe(2)
        ->and($stats['total_evaluations'])->toBe(2)
        ->and($stats['fully_rated_count'])->toBe(1)
        ->and($stats['nds_synced_count'])->toBe(1)
        ->and($stats['retained_count'])->toBe(1)
        ->and($stats['groups_stats'])->toHaveCount(1)
        ->and($stats['groups_stats'][0]['total'])->toBe(2);
});

test('workflow page actions execute correctly via Livewire', function () {
    $admin = User::factory()->create();
    $group = Group::create(['name' => 'Groupe Test']);
    $athlete = Athlete::create([
        'first_name' => 'Claire',
        'last_name' => 'Lemoine',
        'birth_year' => 2010,
        'group_id' => $group->id,
        'status' => AthleteStatus::Active,
    ]);

    $session = EvaluationSession::create([
        'title' => 'Session Livewire Test',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
        'is_closed' => false,
    ]);

    // Test initialization action
    Livewire::actingAs($admin)
        ->test(ManageEvaluationSessionWorkflow::class, ['record' => $session->id])
        ->callAction('initialize_evaluations')
        ->assertHasNoErrors();

    expect(Evaluation::where('evaluation_session_id', $session->id)->count())->toBe(1);

    // Test toggle session closed
    Livewire::actingAs($admin)
        ->test(ManageEvaluationSessionWorkflow::class, ['record' => $session->id])
        ->callAction('toggle_session_closed')
        ->assertHasNoErrors();

    expect($session->fresh()->is_closed)->toBeTrue();
});
