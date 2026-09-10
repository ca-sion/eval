<?php

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationStatus;
use App\Filament\Resources\EvaluationSessions\Pages\ManageEvaluationSessionWorkflow;
use App\Livewire\CoachGroupEvaluation;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('evaluation session can target specific groups and store a submission deadline', function () {
    $groupA = Group::create(['name' => 'Groupe Petits']);
    $groupB = Group::create(['name' => 'Groupe Grands']);

    $session = EvaluationSession::create([
        'title' => 'Session Automne Petits',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-01',
        'submission_deadline' => '2026-10-08',
        'weeks_count' => 4,
    ]);

    $session->groups()->attach([$groupA->id]);

    expect($session->groups)->toHaveCount(1)
        ->and($session->groups->first()->id)->toBe($groupA->id)
        ->and($session->submission_deadline->format('Y-m-d'))->toBe('2026-10-08')
        ->and($session->effectiveSubmissionDeadline()->format('Y-m-d'))->toBe('2026-10-08');
});

test('workflow initialization only generates evaluations for athletes in targeted groups', function () {
    $admin = User::factory()->create();
    $groupPetits = Group::create(['name' => 'Petits', 'default_sessions_per_week' => 2, 'default_competitions_planned' => 3]);
    $groupGrands = Group::create(['name' => 'Grands', 'default_sessions_per_week' => 4, 'default_competitions_planned' => 6]);

    $athlete1 = Athlete::create(['group_id' => $groupPetits->id, 'first_name' => 'Alice', 'last_name' => 'Dupont', 'birth_year' => 2017, 'status' => AthleteStatus::Active]);
    $athlete2 = Athlete::create(['group_id' => $groupPetits->id, 'first_name' => 'Bob', 'last_name' => 'Martin', 'birth_year' => 2016, 'status' => AthleteStatus::Active]);
    $athlete3 = Athlete::create(['group_id' => $groupGrands->id, 'first_name' => 'Charlie', 'last_name' => 'Durand', 'birth_year' => 2010, 'status' => AthleteStatus::Active]);

    $session = EvaluationSession::create([
        'title' => 'Session Petits 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'submission_deadline' => '2026-10-05',
        'weeks_count' => 4,
    ]);
    $session->groups()->attach([$groupPetits->id]);

    Livewire::actingAs($admin)
        ->test(ManageEvaluationSessionWorkflow::class, ['record' => $session->id])
        ->callAction('initialize_evaluations');

    $evals = Evaluation::where('evaluation_session_id', $session->id)->get();

    expect($evals)->toHaveCount(2)
        ->and($evals->pluck('athlete_id')->toArray())->toContain($athlete1->id, $athlete2->id)
        ->and($evals->pluck('athlete_id')->toArray())->not->toContain($athlete3->id);
});

test('coach can edit evaluations after end_date if submission_deadline is later', function () {
    Carbon::setTestNow('2026-10-04'); // Between end_date (2026-09-30) and submission_deadline (2026-10-07)

    $group = Group::create(['name' => 'Groupe Test']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Jean', 'last_name' => 'Test', 'birth_year' => 2012]);

    $session = EvaluationSession::create([
        'title' => 'Session 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'submission_deadline' => '2026-10-07',
        'is_closed' => false,
    ]);
    $session->groups()->attach([$group->id]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'status' => EvaluationStatus::Draft,
    ]);

    expect($eval->canToggleStatus())->toBeTrue()
        ->and($eval->isEditable())->toBeTrue();

    // Coach toggles status to submitted
    Livewire::test(CoachGroupEvaluation::class, ['group' => $group])
        ->call('toggleStatus', $eval->id);

    $eval->refresh();
    expect($eval->status)->toBe(EvaluationStatus::Submitted);

    // After submission deadline: cannot toggle status anymore
    Carbon::setTestNow('2026-10-08');
    expect($eval->canToggleStatus())->toBeFalse();

    Carbon::setTestNow();
});

test('group whatsapp share url contains session title, period and submission deadline', function () {
    $group = Group::create(['name' => 'Poussins']);

    $session = EvaluationSession::create([
        'title' => 'Session Automne 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'submission_deadline' => '2026-10-07',
        'is_closed' => false,
    ]);
    $session->groups()->attach([$group->id]);

    $url = $group->getWhatsAppShareUrl($session);
    $decoded = urldecode($url);

    expect($url)->toContain('https://api.whatsapp.com/send?text=')
        ->and($decoded)->toContain('Bonjour,')
        ->and($decoded)->toContain('Voici le lien pour compléter les évaluations des athlètes du groupe *Poussins*.')
        ->and($decoded)->toContain('*Session :* Session Automne 2026')
        ->and($decoded)->toContain('- Période observée : du 01.09.2026 au 30.09.2026')
        ->and($decoded)->toContain('- Date limite de transmission : *07.10.2026*')
        ->and($decoded)->toContain('Merci pour votre engagement et bonne évaluation !')
        ->and($decoded)->toContain(route('group.mobile', ['group' => $group->access_token]));
});
