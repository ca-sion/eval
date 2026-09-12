<?php

use App\Enums\AthleticLevel;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationDecision;
use App\Enums\EvaluationStatus;
use App\Livewire\CoachGroupEvaluation;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('coach can access mobile interface with valid access token', function () {
    $group = Group::create(['name' => 'U14 Garçons']);

    $response = $this->get(route('group.mobile', ['group' => $group->access_token]));

    $response->assertStatus(200);
    $response->assertSee('U14 Garçons');
    $response->assertSee('CA Sion');
});

test('access token is formatted as readable slug and random suffix', function () {
    $group = Group::create(['name' => 'U14 Garçons']);

    expect($group->access_token)
        ->toStartWith('u14-garcons-')
        ->and(strlen($group->access_token))->toBe(strlen('u14-garcons-') + 10);

    $oldToken = $group->access_token;
    $newToken = $group->regenerateAccessToken();

    expect($newToken)
        ->toStartWith('u14-garcons-')
        ->and($newToken)->not->toBe($oldToken);
});

test('legacy 32-character access tokens remain fully functional for retrocompatibility', function () {
    $legacyToken = 'aBcDeFgHiJkLmNoPqRsTuVwXyZ123456';
    $group = Group::create([
        'name' => 'Ancien Groupe 2025',
        'access_token' => $legacyToken,
    ]);

    $response = $this->get(route('group.mobile', ['group' => $legacyToken]));

    $response->assertStatus(200);
    $response->assertSee('Ancien Groupe 2025');
});

test('updating slug preserves existing token until explicit regeneration', function () {
    $group = Group::create(['name' => 'U14 Garçons']);
    $initialToken = $group->access_token;

    $group->update(['slug' => 'u14-boys-sprint']);

    expect($group->fresh()->slug)->toBe('u14-boys-sprint')
        ->and($group->fresh()->access_token)->toBe($initialToken);

    // Mobile URL still works with original token
    $this->get(route('group.mobile', ['group' => $initialToken]))->assertStatus(200);

    // Regenerating uses the new slug
    $newToken = $group->regenerateAccessToken();
    expect($newToken)->toStartWith('u14-boys-sprint-');
});

test('invalid token returns 404', function () {
    $response = $this->get('/groupe/invalid-token-123456');

    $response->assertStatus(404);
});

test('contextual loading shows all athletes during collective session', function () {
    Carbon::setTestNow('2026-09-15');

    $group = Group::create(['name' => 'U16']);
    $session = EvaluationSession::create([
        'title' => 'Session Automne 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    $athlete1 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Emma', 'last_name' => 'Dubois', 'birth_year' => 2011]);
    $athlete2 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Lucas', 'last_name' => 'Favre', 'birth_year' => 2012]);

    Evaluation::create([
        'athlete_id' => $athlete1->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
    ]);

    Evaluation::create([
        'athlete_id' => $athlete2->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
    ]);

    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->assertSee('Emma Dubois')
        ->assertSee('Lucas Favre')
        ->assertSee('Session Automne 2026');
});

test('updating session dates cascades to existing evaluations and reflects in coach front-end', function () {
    // Initial session dates in October (not today)
    Carbon::setTestNow('2026-09-10');

    $group = Group::create(['name' => 'U16 Cascade']);
    $session = EvaluationSession::create([
        'title' => 'Session Cascade',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
        'weeks_count' => 4,
    ]);

    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Julie', 'last_name' => 'Cascade', 'birth_year' => 2011]);
    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
        'weeks_count' => 4,
    ]);

    // When session is updated to cover today (September 10)
    $session->update([
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'weeks_count' => 4,
    ]);

    // Evaluations must have their dates updated automatically
    expect($eval->fresh()->start_date->format('Y-m-d'))->toBe('2026-09-01')
        ->and($eval->fresh()->end_date->format('Y-m-d'))->toBe('2026-09-30');

    // And coach can immediately see and edit the athlete
    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->assertSee('Julie Cascade')
        ->assertSee('Session Cascade');
});

test('outside collective session only athletes in active cycle are shown', function () {
    Carbon::setTestNow('2026-11-10');

    $group = Group::create(['name' => 'Demi-Fond']);

    $athleteActive = Athlete::create(['group_id' => $group->id, 'first_name' => 'Samy', 'last_name' => 'Nouveau', 'birth_year' => 2010]);
    $athleteOther = Athlete::create(['group_id' => $group->id, 'first_name' => 'Alex', 'last_name' => 'Ancien', 'birth_year' => 2009]);

    // Active adaptation cycle covering 2026-11-10
    Evaluation::create([
        'athlete_id' => $athleteActive->id,
        'group_id' => $group->id,
        'context' => EvaluationContext::Adaptation,
        'start_date' => '2026-11-01',
        'end_date' => '2026-12-06',
        'weeks_count' => 5,
    ]);

    // Old evaluation that finished in October
    Evaluation::create([
        'athlete_id' => $athleteOther->id,
        'group_id' => $group->id,
        'context' => EvaluationContext::Collective,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->assertSee('Samy Nouveau')
        ->assertSee('adaptation')
        ->assertDontSee('Alex Ancien');
});

test('lateness counter increment and decrement update evaluation in real time', function () {
    Carbon::setTestNow('2026-09-15');

    $group = Group::create(['name' => 'U18']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Paul', 'last_name' => 'Roux', 'birth_year' => 2009]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'lateness_count' => 1,
    ]);

    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->call('incrementLateness', $eval->id);

    expect($eval->fresh()->lateness_count)->toEqual(2);

    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->call('decrementLateness', $eval->id);

    expect($eval->fresh()->lateness_count)->toEqual(1);
});

test('coach can toggle injury and set qualitative scores', function () {
    Carbon::setTestNow('2026-09-15');

    $group = Group::create(['name' => 'Sprint']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Sarah', 'last_name' => 'Blanc', 'birth_year' => 2011]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'is_injured' => false,
    ]);

    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->call('toggleInjury', $eval->id)
        ->call('setScore', $eval->id, 'c4_commitment', '8')
        ->call('setScore', $eval->id, 'c5_behavior', 7.5)
        ->call('setLevel', $eval->id, AthleticLevel::National->value)
        ->call('updateNotes', $eval->id, 'Excellent engagement');

    $fresh = $eval->fresh();
    expect($fresh->is_injured)->toBeTrue()
        ->and($fresh->c4_commitment)->toEqual(8.0)
        ->and($fresh->c5_behavior)->toEqual(7.5)
        ->and($fresh->c6_level)->toEqual(AthleticLevel::National)
        ->and($fresh->coach_notes)->toEqual('Excellent engagement');

    // Test resetting score with empty string
    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->call('setScore', $eval->id, 'c4_commitment', '')
        ->call('setLevel', $eval->id, '');

    $fresh = $eval->fresh();
    expect($fresh->c4_commitment)->toBeNull()
        ->and($fresh->c6_level)->toBeNull();
});

test('strict confidentiality is preserved in coach view', function () {
    Carbon::setTestNow('2026-09-15');

    $group = Group::create(['name' => 'U16 Confidentiel']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Secret', 'last_name' => 'Agent', 'birth_year' => 2011]);

    Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'real_attendances' => 14,
        'competitions_done' => 5,
        'parent_volunteering_count' => 3,
        'final_score' => 8.87,
        'base_average' => 8.00,
        'rank' => 1,
        'decision' => EvaluationDecision::Retained,
    ]);

    $response = $this->get(route('group.mobile', ['group' => $group->access_token]));

    // Check that sensitive calculations and decision info are NOT in the view
    $response->assertDontSee('8.87');
    $response->assertDontSee('base_average');
    $response->assertDontSee('final_score');
    $response->assertDontSee('Retenu (quota)');
    $response->assertDontSee('Bénévolat des parents');
});

test('temporal lock disables editing after end_date has passed', function () {
    Carbon::setTestNow('2026-10-20'); // Past the end_date

    $group = Group::create(['name' => 'U16']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Luc', 'last_name' => 'Fermé', 'birth_year' => 2011]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'lateness_count' => 1,
    ]);

    // Attempting to increment lateness must be rejected because evaluation is not editable
    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->call('incrementLateness', $eval->id);

    expect($eval->fresh()->lateness_count)->toEqual(1);
});

test('coach can lock and unlock an athlete evaluation at will during active session', function () {
    Carbon::setTestNow('2026-09-15');

    $group = Group::create(['name' => 'U16']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Emma', 'last_name' => 'Val', 'birth_year' => 2011]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'status' => EvaluationStatus::Draft,
        'lateness_count' => 0,
    ]);

    // 1. Initially Draft: isEditable is true, coach sees "Brouillon"
    $test = Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->assertSee('Brouillon')
        ->call('incrementLateness', $eval->id);

    expect($eval->fresh()->lateness_count)->toEqual(1);

    // 2. Coach locks the athlete (Submitted)
    $test->call('toggleStatus', $eval->id)
        ->assertSee('Verrouillé');

    expect($eval->fresh()->status)->toEqual(EvaluationStatus::Submitted);

    // When locked, increments are blocked
    $test->call('incrementLateness', $eval->id);
    expect($eval->fresh()->lateness_count)->toEqual(1);

    // 3. Coach unlocks the athlete (back to Draft)
    $test->call('toggleStatus', $eval->id)
        ->assertSee('Brouillon');

    expect($eval->fresh()->status)->toEqual(EvaluationStatus::Draft);

    // Now editing works again
    $test->call('incrementLateness', $eval->id);
    expect($eval->fresh()->lateness_count)->toEqual(2);
});
