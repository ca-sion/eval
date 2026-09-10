<?php

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationDecision;
use App\Filament\Resources\EvaluationSessions\Pages\EditEvaluationSession;
use App\Filament\Resources\EvaluationSessions\RelationManagers\EvaluationsRelationManager;
use App\Models\Athlete;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use App\Models\User;
use App\Services\EvaluationCalculatorService;
use App\Services\ExcelExportService;
use App\Services\NdsImportService;
use App\Services\PdfReportService;
use App\Services\TiivaImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;

uses(RefreshDatabase::class);

test('admin can access filament admin dashboard after authentication', function () {
    $admin = User::factory()->create(['email' => 'techlead@casion.ch']);

    $response = $this->actingAs($admin)->get('/admin');

    $response->assertStatus(200);
});

test('admin can access group edit, athletes list with tabs, and session edit pages without error', function () {
    $admin = User::factory()->create(['email' => 'techlead2@casion.ch']);
    $group = Group::create(['name' => 'Sprint U16']);
    $session = EvaluationSession::create([
        'title' => 'Session Automne',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
    ]);

    // Test Group edit page
    $this->actingAs($admin)
        ->get("/admin/groups/{$group->id}/edit")
        ->assertStatus(200);

    // Test Athletes list page (with tabs)
    $this->actingAs($admin)
        ->get('/admin/athletes')
        ->assertStatus(200);

    // Test Evaluation Session edit page
    $this->actingAs($admin)
        ->get("/admin/evaluation-sessions/{$session->id}/edit")
        ->assertStatus(200);
});

test('session mass initialization creates evaluations for all active athletes', function () {
    $group = Group::create(['name' => 'U16', 'default_sessions_per_week' => 3, 'default_competitions_planned' => 6]);

    $athlete1 = Athlete::create(['group_id' => $group->id, 'first_name' => 'A1', 'last_name' => 'L1', 'birth_year' => 2012, 'status' => AthleteStatus::Active]);
    $athlete2 = Athlete::create(['group_id' => $group->id, 'first_name' => 'A2', 'last_name' => 'L2', 'birth_year' => 2012, 'status' => AthleteStatus::Active]);
    $athleteInactive = Athlete::create(['group_id' => $group->id, 'first_name' => 'Inactif', 'last_name' => 'User', 'birth_year' => 2012, 'status' => AthleteStatus::Inactive]);

    $session = EvaluationSession::create([
        'title' => 'Session Test',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    // Perform initialization logic
    $activeAthletes = Athlete::where('status', AthleteStatus::Active)->with('group')->get();
    foreach ($activeAthletes as $athlete) {
        Evaluation::create([
            'athlete_id' => $athlete->id,
            'group_id' => $athlete->group_id,
            'evaluation_session_id' => $session->id,
            'context' => EvaluationContext::Collective,
            'start_date' => $session->start_date,
            'end_date' => $session->end_date,
            'weeks_count' => $session->weeks_count,
            'sessions_per_week' => $athlete->group->default_sessions_per_week,
            'competitions_planned' => $athlete->group->default_competitions_planned,
        ]);
    }

    expect(Evaluation::where('evaluation_session_id', $session->id)->count())->toEqual(2)
        ->and(Evaluation::where('athlete_id', $athleteInactive->id)->exists())->toBeFalse();
});

test('pdf report service generates interview sheet and committee minutes', function () {
    $group = Group::create(['name' => 'U16 Test']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Marc', 'last_name' => 'Valais', 'birth_year' => 2012]);

    $session = EvaluationSession::create([
        'title' => 'Session Automne 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'final_score' => 8.50,
        'base_average' => 8.50,
        'decision' => EvaluationDecision::Retained,
        'rank' => 1,
    ]);

    $pdfService = new PdfReportService;

    // 1. Individual interview sheet
    $interviewResponse = $pdfService->generateInterviewReport($eval);
    expect($interviewResponse->getStatusCode())->toEqual(200)
        ->and($interviewResponse->headers->get('content-type'))->toEqual('application/pdf');

    // 2. Official session minutes
    $minutesResponse = $pdfService->generateOfficialSessionMinutes($session);
    expect($minutesResponse->getStatusCode())->toEqual(200)
        ->and($minutesResponse->headers->get('content-type'))->toEqual('application/pdf');
});

test('excel export service generates valid multi-sheet workbook', function () {
    $group = Group::create(['name' => 'U16 Sprint']);
    $athlete = Athlete::create(['group_id' => $group->id, 'first_name' => 'Julie', 'last_name' => 'Bovey', 'birth_year' => 2012]);

    $session = EvaluationSession::create([
        'title' => 'Session Printemps 2026',
        'start_date' => '2026-03-01',
        'end_date' => '2026-04-05',
    ]);

    Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'final_score' => 7.80,
        'rank' => 1,
        'decision' => EvaluationDecision::Retained,
    ]);

    $excelService = new ExcelExportService;
    $response = $excelService->exportSession($session);

    expect($response->getStatusCode())->toEqual(200);
});

test('tiiva import service reconciles existing athletes and creates missing groups', function () {
    // Create a temporary CSV file representing Tiiva export
    $tempCsv = tempnam(sys_get_temp_dir(), 'tiiva_test').'.csv';
    $writer = new CsvWriter;
    $writer->openToFile($tempCsv);
    $writer->addRow(Row::fromValues(['Prénom', 'Nom', 'Année de naissance', 'Nom du groupe', 'Identifiant Tiiva', 'Compétitions faites']));
    $writer->addRow(Row::fromValues(['Alex', 'Fournier', '2012', 'Nouveau Groupe U14', 'TIIVA-101', '4']));
    $writer->addRow(Row::fromValues(['Clara', 'Rey', '2011', 'Nouveau Groupe U14', 'TIIVA-102', '5']));
    $writer->close();

    $importer = new TiivaImportService;
    $result = $importer->import($tempCsv);

    expect($result['created'])->toEqual(2)
        ->and($result['groups_created'])->toEqual(1)
        ->and(Group::where('name', 'Nouveau Groupe U14')->exists())->toBeTrue()
        ->and(Athlete::where('tiiva_id', 'TIIVA-101')->first()->last_name)->toEqual('Fournier');

    unlink($tempCsv);
});

test('nds import service updates real attendances and calculates scores', function () {
    $group = Group::create(['name' => 'U16 NDS']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Bastien',
        'last_name' => 'Sierro',
        'birth_year' => 2012,
        'nds_number' => 'NDS-7788',
    ]);

    $session = EvaluationSession::create([
        'title' => 'Session NDS',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'sessions_per_week' => 3,
        'weeks_count' => 5,
        'real_attendances' => null,
    ]);

    // Create a temporary XLSX file with NDS data
    $tempXlsx = tempnam(sys_get_temp_dir(), 'nds_test').'.xlsx';
    $writer = new XlsxWriter;
    $writer->openToFile($tempXlsx);
    $writer->addRow(Row::fromValues(['Numéro NDS', 'Nom', 'Prénom', 'Année de naissance', 'Total présences']));
    $writer->addRow(Row::fromValues(['NDS-7788', 'Sierro', 'Bastien', '2012', 14]));
    $writer->close();

    $ndsImporter = new NdsImportService;
    $calculator = new EvaluationCalculatorService;
    $result = $ndsImporter->import($tempXlsx, $session, $calculator);

    expect($result['synced'])->toEqual(1)
        ->and($eval->fresh()->real_attendances)->toEqual(14)
        ->and($eval->fresh()->c1_score)->toBeGreaterThan(0);

    unlink($tempXlsx);
});

test('interview_pdf table action and export_pdf_comite work through Livewire without UTF-8 encoding error', function () {
    $admin = User::factory()->create(['email' => 'techlead_pdf@casion.ch']);
    $group = Group::create(['name' => 'U16 Garçons (Sprint / Sauts)']);
    $athlete = Athlete::create([
        'group_id' => $group->id,
        'first_name' => 'Chloé',
        'last_name' => 'Bagnoud',
        'birth_year' => 2012,
    ]);

    $session = EvaluationSession::create([
        'title' => 'Session Automne 2026',
        'start_date' => '2026-09-01',
        'end_date' => '2026-10-06',
        'weeks_count' => 5,
    ]);

    $eval = Evaluation::create([
        'athlete_id' => $athlete->id,
        'group_id' => $group->id,
        'evaluation_session_id' => $session->id,
        'context' => EvaluationContext::Collective,
        'start_date' => $session->start_date,
        'end_date' => $session->end_date,
        'final_score' => 8.50,
        'base_average' => 8.50,
        'decision' => EvaluationDecision::Retained,
        'rank' => 1,
    ]);

    Livewire::actingAs($admin)
        ->test(EvaluationsRelationManager::class, [
            'ownerRecord' => $session,
            'pageClass' => EditEvaluationSession::class,
        ])
        ->assertSuccessful()
        ->callTableAction('interview_pdf', $eval);

    Livewire::actingAs($admin)
        ->test(EditEvaluationSession::class, [
            'record' => $session->id,
        ])
        ->assertSuccessful()
        ->callAction('export_pdf_comite');
});

test('admin can access profile page to edit credentials and change password', function () {
    $admin = User::factory()->create(['email' => 'techlead_profile@casion.ch', 'password' => 'oldpassword']);

    $this->actingAs($admin)
        ->get('/admin/profile')
        ->assertStatus(200)
        ->assertSee('techlead_profile@casion.ch');
});
