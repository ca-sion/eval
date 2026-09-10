<?php

namespace App\Http\Controllers;

use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Services\PdfReportService;
use Illuminate\Http\Response;

class PdfReportController extends Controller
{
    /**
     * Affiche le flux PDF de la fiche individuelle d'entretien.
     */
    public function interviewReport(Evaluation $evaluation, PdfReportService $pdfService): Response
    {
        return $pdfService->generateInterviewReport($evaluation);
    }

    /**
     * Affiche le flux PDF du Procès-Verbal officiel pour le Comité.
     */
    public function sessionMinutes(EvaluationSession $session, PdfReportService $pdfService): Response
    {
        return $pdfService->generateOfficialSessionMinutes($session);
    }
}
