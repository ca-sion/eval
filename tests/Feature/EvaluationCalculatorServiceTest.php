<?php

use App\Enums\ArbitrageMode;
use App\Enums\AthleticLevel;
use App\Enums\EvaluationDecision;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\Group;
use App\Services\EvaluationCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('c1 assiduity calculates correctly when attendances provided', function () {
    $group = Group::create(['name' => 'U16 Sprint']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'birth_year' => 2012,
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
        'sessions_per_week' => 3, // total expected = 15
        'real_attendances' => 12, // 12 / 15 * 10 = 8.0
    ]);

    $service = new EvaluationCalculatorService;
    $service->calculateAthlete($eval);

    expect($eval->c1_score)->toEqual(8.0);
});

test('c2 lateness deducts 1.5 points per late arrival and is bounded at 0', function () {
    $group = Group::create(['name' => 'U16 Sprint']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'birth_year' => 2012,
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'lateness_count' => 2, // 10 - (2 * 1.5) = 7.0
    ]);

    $service = new EvaluationCalculatorService;
    $service->calculateAthlete($eval);
    expect($eval->c2_score)->toEqual(7.0);

    $eval->lateness_count = 10; // 10 - 15 = max(0, -5) = 0
    $service->calculateAthlete($eval);
    expect($eval->c2_score)->toEqual(0.0);
});

test('c3 competitions calculates ratio of done over planned', function () {
    $group = Group::create(['name' => 'U16 Sprint']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'birth_year' => 2012,
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'competitions_planned' => 5,
        'competitions_done' => 4, // 4 / 5 * 10 = 8.0
    ]);

    $service = new EvaluationCalculatorService;
    $service->calculateAthlete($eval);

    expect($eval->c3_score)->toEqual(8.0);
});

test('c6 athletic level scores match specified tiers', function () {
    $group = Group::create(['name' => 'U16 Sprint']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Jean',
        'last_name' => 'Dupont',
        'birth_year' => 2012,
    ]);

    $service = new EvaluationCalculatorService;

    $tiers = [
        AthleticLevel::Regional->value => 6.0,
        AthleticLevel::Romand->value => 7.5,
        AthleticLevel::National->value => 9.0,
        AthleticLevel::International->value => 10.0,
    ];

    foreach ($tiers as $tierValue => $expectedScore) {
        $eval = Evaluation::create([
            'athlete_id' => $athlete->id,
            'group_id' => $group->id,
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-06',
            'c6_level' => $tierValue,
        ]);
        $service->calculateAthlete($eval);
        expect($eval->c6_score)->toEqual($expectedScore);
    }
});

test('c9 parent volunteering respects age limit', function () {
    $group = Group::create([
        'name' => 'Cadets',
        'max_volunteering_age' => 14,
        'required_volunteering_count' => 2,
    ]);

    // Athlete 1: 14 years old in 2026 (born 2012) -> eligible for volunteering criterion
    $youngAthlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Alice',
        'last_name' => 'Valais',
        'birth_year' => 2012,
    ]);

    // Athlete 2: 17 years old in 2026 (born 2009) -> strictly > 14 -> neutralized (null)
    $olderAthlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Bob',
        'last_name' => 'Sion',
        'birth_year' => 2009,
    ]);

    $service = new EvaluationCalculatorService;

    // Young athlete with 2 required volunteering events done -> 7.5
    $evalYoung = Evaluation::create([
        'athlete_id' => $youngAthlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'parent_volunteering_count' => 2,
    ]);
    $service->calculateAthlete($evalYoung);
    expect($evalYoung->c9_score)->toEqual(7.5);

    // Young athlete with 3 volunteering events done (1 extra) -> 7.5 + 1.25 = 8.75
    $evalYoung->parent_volunteering_count = 3;
    $service->calculateAthlete($evalYoung);
    expect($evalYoung->c9_score)->toEqual(8.75);

    // Older athlete -> must be neutralized (null)
    $evalOlder = Evaluation::create([
        'athlete_id' => $olderAthlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'parent_volunteering_count' => 5,
    ]);
    $service->calculateAthlete($evalOlder);
    expect($evalOlder->c9_score)->toBeNull();
});

test('injury neutralizes c1 and c3 and redistributes remaining weights dynamically', function () {
    $group = Group::create(['name' => 'U16']);
    // Older athlete (born 2005, age 21 > 14) so C9 is also neutralized (null)
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Marc',
        'last_name' => 'Bovey',
        'birth_year' => 2005,
    ]);

    $service = new EvaluationCalculatorService;

    // C1 is neutralized due to injury (null)
    // C3 is neutralized due to injury (null)
    // C9 is neutralized due to age (null)
    // Active criteria:
    // C2 (lateness=0 -> 10.0, weight 0.05) -> 0.5
    // C4 (commitment=8.0, weight 0.15) -> 1.2
    // C5 (behavior=6.0, weight 0.15) -> 0.9
    // Sum weights = 0.05 + 0.15 + 0.15 = 0.35
    // Sum weighted scores = 0.5 + 1.2 + 0.9 = 2.6
    // Expected base_average = 2.6 / 0.35 = 7.42857 -> 7.43
    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'is_injured' => true,
        'real_attendances' => 15,
        'competitions_done' => 5,
        'lateness_count' => 0,
        'c4_commitment' => 8.0,
        'c5_behavior' => 6.0,
    ]);

    $service->calculateAthlete($eval);

    expect($eval->c1_score)->toBeNull()
        ->and($eval->c3_score)->toBeNull()
        ->and($eval->c9_score)->toBeNull()
        ->and($eval->base_average)->toEqual(7.43)
        ->and($eval->final_score)->toEqual(7.43);
});

test('club engagement bonus adds 0.75 and caps at 10.00', function () {
    $group = Group::create(['name' => 'Elite']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Léa',
        'last_name' => 'Giroud',
        'birth_year' => 2008,
    ]);

    $service = new EvaluationCalculatorService;

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'lateness_count' => 0,
        'c4_commitment' => 8.0,
        'c5_behavior' => 8.0,
        'has_club_engagement' => true,
    ]);

    $service->calculateAthlete($eval);

    expect($eval->bonus_points)->toEqual(0.75)
        ->and($eval->final_score)->toBeGreaterThan($eval->base_average);

    // Test capping at 10.00
    $evalMax = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'lateness_count' => 0,
        'c4_commitment' => 10.0,
        'c5_behavior' => 10.0,
        'c7_progress' => 10.0,
        'c8_sports_hygiene' => 10.0,
        'c6_level' => AthleticLevel::International,
        'has_club_engagement' => true,
    ]);
    $service->calculateAthlete($evalMax);
    expect($evalMax->final_score)->toEqual(10.00);
});

test('safety guard returns null when no criteria are scored and prevents division by zero', function () {
    $group = Group::create(['name' => 'Cadets']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Nouveau',
        'last_name' => 'Membre',
        'birth_year' => 2012,
    ]);

    // Force all criteria weights to 0 to simulate zero active criteria weight
    config(['evaluation.weights' => [
        'c1' => 0.0, 'c2' => 0.0, 'c3' => 0.0, 'c4' => 0.0,
        'c5' => 0.0, 'c6' => 0.0, 'c7' => 0.0, 'c8' => 0.0, 'c9' => 0.0,
    ]]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
    ]);

    $service = new EvaluationCalculatorService;
    $service->calculateAthlete($eval);

    expect($eval->base_average)->toBeNull()
        ->and($eval->final_score)->toBeNull();
});

test('arbitrateGroup ranks athletes and applies Quota decisions correctly', function () {
    $group = Group::create([
        'name' => 'Groupe Quota',
        'arbitration_mode' => ArbitrageMode::Quota,
        'quota_places' => 2,
    ]);

    // Athletes aged > 14 to neutralize C9, and competitions_planned = 0 to neutralize C3
    $athlete1 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Ath1', 'last_name' => 'A', 'birth_year' => 2005]);
    $athlete2 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Ath2', 'last_name' => 'B', 'birth_year' => 2005]);
    $athlete3 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Ath3', 'last_name' => 'C', 'birth_year' => 2005]);
    $athlete4 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Ath4', 'last_name' => 'D', 'birth_year' => 2005]);

    $eval1 = Evaluation::create([
        'athlete_id' => $athlete1->id, 'group_id' => $group->id, 'start_date' => '2026-09-01', 'end_date' => '2026-10-06',
        'competitions_planned' => 0, 'c4_commitment' => 9.0, 'c5_behavior' => 9.0,
    ]);
    $eval2 = Evaluation::create([
        'athlete_id' => $athlete2->id, 'group_id' => $group->id, 'start_date' => '2026-09-01', 'end_date' => '2026-10-06',
        'competitions_planned' => 0, 'c4_commitment' => 8.0, 'c5_behavior' => 8.0,
    ]);
    $eval3 = Evaluation::create([
        'athlete_id' => $athlete3->id, 'group_id' => $group->id, 'start_date' => '2026-09-01', 'end_date' => '2026-10-06',
        'competitions_planned' => 0, 'c4_commitment' => 6.5, 'c5_behavior' => 6.5,
    ]);
    $eval4 = Evaluation::create([
        'athlete_id' => $athlete4->id, 'group_id' => $group->id, 'start_date' => '2026-09-01', 'end_date' => '2026-10-06',
        'competitions_planned' => 0, 'c4_commitment' => 2.0, 'c5_behavior' => 2.0, 'lateness_count' => 6,
    ]);

    $service = new EvaluationCalculatorService;
    $results = $service->arbitrateGroup($group);

    // Rank 1 and 2 (within quota 2) must be Retained
    expect($results[0]->rank)->toEqual(1)
        ->and($results[0]->decision)->toEqual(EvaluationDecision::Retained)
        ->and($results[1]->rank)->toEqual(2)
        ->and($results[1]->decision)->toEqual(EvaluationDecision::Retained);

    // Rank 3 (beyond quota, score >= 6.0) must be ProbationNeeded
    expect($results[2]->rank)->toEqual(3)
        ->and($results[2]->final_score)->toBeGreaterThanOrEqual(6.0)
        ->and($results[2]->decision)->toEqual(EvaluationDecision::ProbationNeeded);

    // Rank 4 (beyond quota, score < 6.0) must be NotRetained
    expect($results[3]->rank)->toEqual(4)
        ->and($results[3]->final_score)->toBeLessThan(6.0)
        ->and($results[3]->decision)->toEqual(EvaluationDecision::NotRetained);
});

test('arbitrateGroup applies Threshold decisions correctly', function () {
    $group = Group::create([
        'name' => 'Groupe Threshold',
        'arbitration_mode' => ArbitrageMode::Threshold,
        'min_score' => 7.5,
    ]);

    $athlete1 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Ath1', 'last_name' => 'A', 'birth_year' => 2005]);
    $athlete2 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Ath2', 'last_name' => 'B', 'birth_year' => 2005]);
    $athlete3 = Athlete::create(['group_id' => $group->id, 'first_name' => 'Ath3', 'last_name' => 'C', 'birth_year' => 2005]);

    $eval1 = Evaluation::create([
        'athlete_id' => $athlete1->id, 'group_id' => $group->id, 'start_date' => '2026-09-01', 'end_date' => '2026-10-06',
        'competitions_planned' => 0, 'c4_commitment' => 9.0, 'c5_behavior' => 9.0, // Score >= 7.5
    ]);
    $eval2 = Evaluation::create([
        'athlete_id' => $athlete2->id, 'group_id' => $group->id, 'start_date' => '2026-09-01', 'end_date' => '2026-10-06',
        'competitions_planned' => 0, 'c4_commitment' => 6.2, 'c5_behavior' => 6.2, // Score between 6.0 and 7.5
    ]);
    $eval3 = Evaluation::create([
        'athlete_id' => $athlete3->id, 'group_id' => $group->id, 'start_date' => '2026-09-01', 'end_date' => '2026-10-06',
        'competitions_planned' => 0, 'c4_commitment' => 2.0, 'c5_behavior' => 2.0, 'lateness_count' => 6, // Score < 6.0
    ]);

    $service = new EvaluationCalculatorService;
    $results = $service->arbitrateGroup($group);

    // Athlete 1 >= 7.5 -> Retained
    expect($results[0]->decision)->toEqual(EvaluationDecision::Retained);

    // Athlete 2 < 7.5 but >= 6.0 -> ProbationNeeded
    expect($results[1]->decision)->toEqual(EvaluationDecision::ProbationNeeded);

    // Athlete 3 < 6.0 -> NotRetained
    expect($results[2]->decision)->toEqual(EvaluationDecision::NotRetained);
});
