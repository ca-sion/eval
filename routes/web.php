<?php

use App\Http\Controllers\PdfReportController;
use App\Livewire\CoachGroupEvaluation;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

Route::get('/groupe/{group:access_token}', CoachGroupEvaluation::class)->name('group.mobile');

// Routes de streaming PDF (visualisation directe dans le navigateur)
Route::get('/evaluations/{evaluation}/bilan-pdf', [PdfReportController::class, 'interviewReport'])->name('evaluations.pdf');
Route::get('/sessions/{session}/pv-comite-pdf', [PdfReportController::class, 'sessionMinutes'])->name('evaluation-sessions.minutes.pdf');
