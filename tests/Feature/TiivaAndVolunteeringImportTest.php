<?php

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use App\Services\EvaluationCalculatorService;
use App\Services\NdsImportService;
use App\Services\TiivaApiService;
use App\Services\VolunteerImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

uses(RefreshDatabase::class);

test('tiiva api service correctly synchronizes groups and contacts with legal guardians', function () {
    config()->set('services.tiiva.api_url', 'https://tiiva.test/api');
    config()->set('services.tiiva.api_token', 'fake-token');

    // Fake HTTP responses for contact-groups and contacts per group
    Http::fake([
        'https://tiiva.test/api/v1/contact-groups*' => Http::response([
            'data' => [
                [
                    'id' => '101',
                    'attributes' => [
                        'name' => 'Sprint U18',
                        'color' => '#3b82f6',
                        'order' => 1,
                        'is_training_group' => true,
                    ],
                ],
                [
                    'id' => '102',
                    'attributes' => [
                        'name' => 'Demi-Fond U16',
                        'color' => '#10b981',
                        'order' => 2,
                        'is_training_group' => true,
                    ],
                ],
            ],
            'links' => [
                'next' => null,
            ],
        ], 200),
        'https://tiiva.test/api/v1/contacts*filter%5Bgroup_id%5D=101*' => Http::response([
            'data' => [
                [
                    'id' => '501',
                    'attributes' => [
                        'first_name' => 'Lucas',
                        'last_name' => 'Favre',
                        'birthday' => '2010-04-15',
                        'gender' => 'M',
                        'email' => 'lucas@favre.ch',
                        'phone' => '+41 79 111 22 33',
                        'is_active' => true,
                    ],
                    'relationships' => [
                        'groups' => [
                            'data' => [
                                ['id' => '101', 'type' => 'contact-groups'],
                            ],
                        ],
                        'guardians' => [
                            'data' => [
                                ['id' => '7080', 'type' => 'contacts'],
                                ['id' => '7081', 'type' => 'contacts'],
                            ],
                        ],
                    ],
                ],
            ],
            'links' => [
                'next' => null,
            ],
        ], 200),
        'https://tiiva.test/api/v1/contacts*filter%5Bgroup_id%5D=102*' => Http::response([
            'data' => [
                [
                    'id' => '502',
                    'attributes' => [
                        'first_name' => 'Emma',
                        'last_name' => 'Bonvin',
                        'birthday' => '2011-08-20',
                        'gender' => 'F',
                        'email' => 'emma@bonvin.ch',
                        'phone' => '+41 79 444 55 66',
                        'is_active' => true,
                    ],
                    'relationships' => [
                        'groups' => [
                            'data' => [
                                ['id' => '102', 'type' => 'contact-groups'],
                            ],
                        ],
                        'guardians' => [
                            'data' => [
                                ['id' => '7090', 'type' => 'contacts'],
                            ],
                        ],
                    ],
                ],
            ],
            'links' => [
                'next' => null,
            ],
        ], 200),
        'https://tiiva.test/api/v1/contacts*' => Http::response([
            'data' => [],
            'links' => ['next' => null],
        ], 200),
    ]);

    $service = new TiivaApiService;

    $session = EvaluationSession::create([
        'title' => 'Session Automne 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    $existingGroup = Group::create([
        'name' => 'Ancien Groupe',
        'tiiva_id' => 999,
        'default_sessions_per_week' => 3,
        'default_competitions_planned' => 6,
    ]);

    // Create a previously existing athlete who is no longer in Tiiva to verify deactivation
    $oldAthlete = Athlete::create([
        'first_name' => 'Ancien',
        'last_name' => 'Membre',
        'birth_year' => 2009,
        'group_id' => $existingGroup->id,
        'status' => AthleteStatus::Active,
        'tiiva_id' => '400',
    ]);

    $result = $service->syncAll($session);

    expect($result['success'])->toBeTrue()
        ->and($result['groups_synced'])->toBe(2)
        ->and($result['athletes_synced'])->toBe(2)
        ->and($result['athletes_deactivated'])->toBe(1)
        ->and($result['evaluations_created'])->toBe(2);

    $lucas = Athlete::where('tiiva_id', '501')->first();
    expect($lucas)->not->toBeNull()
        ->and($lucas->first_name)->toBe('Lucas')
        ->and($lucas->last_name)->toBe('Favre')
        ->and($lucas->guardian_tiiva_ids)->toBe(['7080', '7081'])
        ->and($lucas->group->name)->toBe('Sprint U18');

    // The old athlete is now inactive
    expect($oldAthlete->fresh()->status)->toBe(AthleteStatus::Inactive);

    // Session last_tiiva_synced_at is recorded
    expect($session->fresh()->last_tiiva_synced_at)->not->toBeNull();

    $evaluation = Evaluation::where('athlete_id', $lucas->id)->where('evaluation_session_id', $session->id)->first();
    expect($evaluation)->not->toBeNull()
        ->and($evaluation->context)->toBe(EvaluationContext::Collective);
});

test('volunteer import service reconciles legal guardian participations and credits athlete', function () {
    $group = Group::create(['name' => 'Groupe Test', 'default_sessions_per_week' => 3, 'default_competitions_planned' => 6]);
    $session = EvaluationSession::create([
        'title' => 'Session Automne 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    $athlete = Athlete::create([
        'first_name' => 'Lucas',
        'last_name' => 'Favre',
        'group_id' => $group->id,
        'status' => AthleteStatus::Active,
        'tiiva_id' => '501',
        'guardian_tiiva_ids' => ['7080', '7081'],
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'weeks_count' => 5,
        'sessions_per_week' => 3,
        'competitions_planned' => 6,
        'parent_volunteering_count' => 0,
    ]);

    // Create a mock volunteering matrix XLSX with Tiiva contact IDs
    $tempFile = tempnam(sys_get_temp_dir(), 'vol_').'.xlsx';
    $writer = new XlsxWriter;
    $writer->openToFile($tempFile);

    // Header row
    $writer->addRow(Row::fromValues(['ID', 'Nom', 'Prénom', 'Événement 1', 'Événement 2', 'Total participations']));
    // Guardian 7080 (Parent 1) participated in 2 events
    $writer->addRow(Row::fromValues(['7080', 'Favre', 'Jean-Pierre', 'Oui', 'Oui', 2]));
    // Guardian 7081 (Parent 2) participated in 1 event
    $writer->addRow(Row::fromValues(['7081', 'Favre', 'Marie', 'Oui', '', 1]));
    // Another unrelated contact 9999
    $writer->addRow(Row::fromValues(['9999', 'Autre', 'Personne', 'Oui', '', 1]));
    $writer->close();

    $service = app(VolunteerImportService::class);
    $calculator = app(EvaluationCalculatorService::class);
    $result = $service->import($tempFile, $session, $calculator);

    unlink($tempFile);

    expect($result['synced'])->toBe(1);

    $eval->refresh();
    // 2 + 1 = 3 participations for Lucas's guardians
    expect($eval->parent_volunteering_count)->toBe(3);
});

test('nds import service parses complex official J+S multi-column worksheet and filters session dates', function () {
    $group = Group::create(['name' => 'Groupe Test NDS', 'default_sessions_per_week' => 3, 'default_competitions_planned' => 6]);
    $session = EvaluationSession::create([
        'title' => 'Session NDS',
        'start_date' => '2026-09-01',
        'end_date' => '2026-09-30',
        'weeks_count' => 4,
    ]);

    $athlete = Athlete::create([
        'first_name' => 'Sophie',
        'last_name' => 'Rey',
        'birthday' => '2010-05-12',
        'group_id' => $group->id,
        'status' => AthleteStatus::Active,
        'nds_number' => 'NDS-12345',
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'weeks_count' => 4,
        'sessions_per_week' => 3,
        'competitions_planned' => 6,
    ]);

    // Create official NDS multi-column workbook
    $tempFile = tempnam(sys_get_temp_dir(), 'nds_').'.xlsx';
    $writer = new XlsxWriter;
    $writer->openToFile($tempFile);

    // Row 1-5 metadata
    $writer->addRow(Row::fromValues(['Liste de présence Jeunesse+Sport']));
    $writer->addRow(Row::fromValues([]));
    $writer->addRow(Row::fromValues([]));
    $writer->addRow(Row::fromValues([]));
    $writer->addRow(Row::fromValues([]));

    // Row 6: Date row (Col 0 = "Date", Col 5 = '2026-08-25' outside, Col 6 = '2026-09-05' inside, Col 7 = '2026-09-12' inside, Col 8 = '2026-10-15' outside)
    $writer->addRow(Row::fromValues([
        'Date', '', '', '', '',
        new DateTimeImmutable('2026-08-25'),
        new DateTimeImmutable('2026-09-05'),
        new DateTimeImmutable('2026-09-12'),
        new DateTimeImmutable('2026-10-15'),
    ]));

    // Row 7-20 other header lines
    for ($i = 7; $i <= 20; $i++) {
        $writer->addRow(Row::fromValues(["Ligne $i"]));
    }

    // Row 21: Participant section delimiter
    $writer->addRow(Row::fromValues(['Participant/e(X):']));

    // Row 22: Athlete row (Rey Sophie) - attended on 2026-08-25 (outside), 2026-09-05 (inside), 2026-09-12 (inside), 2026-10-15 (outside)
    $writer->addRow(Row::fromValues([
        '1',
        'Rey',
        'Sophie',
        new DateTimeImmutable('2010-05-12'),
        'F',
        'J', // Aug 25 - excluded
        'J', // Sep 05 - included
        'J', // Sep 12 - included
        'J', // Oct 15 - excluded
    ]));

    $writer->close();

    $service = app(NdsImportService::class);
    $calculator = app(EvaluationCalculatorService::class);
    $result = $service->import($tempFile, $session, $calculator);

    unlink($tempFile);

    expect($result['synced'])->toBe(1);

    $eval->refresh();
    // Only 2 attendances within 2026-09-01 and 2026-09-30
    expect($eval->real_attendances)->toBe(2);
});
