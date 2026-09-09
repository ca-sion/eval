<?php

namespace App\Services;

use App\Enums\EvaluationDecision;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfReportService
{
    /**
     * Génère la fiche d'entretien individuelle en PDF.
     */
    public function generateInterviewReport(Evaluation $evaluation): StreamedResponse
    {
        $evaluation->loadMissing(['athlete', 'group', 'session']);

        $pdf = Pdf::loadView('pdf.interview-sheet', [
            'evaluation' => $evaluation,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf(
            'fiche_entretien_%s_%s.pdf',
            str_replace(' ', '_', strtolower($evaluation->athlete->last_name)),
            $evaluation->start_date->format('Y_m')
        );

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Génère le Procès-Verbal officiel destiné au Comité du club.
     */
    public function generateOfficialSessionMinutes(EvaluationSession $session): StreamedResponse
    {
        $session->loadMissing('evaluations.athlete', 'evaluations.group');

        $evaluationsByGroup = $session->evaluations
            ->groupBy('group_id');

        $groupsData = [];
        foreach ($evaluationsByGroup as $groupId => $evals) {
            $group = Group::find($groupId);
            if (! $group) {
                continue;
            }

            $sortedEvals = $evals->sortBy(fn (Evaluation $e) => $e->rank ?? 999)->values();

            $groupsData[] = [
                'group' => $group,
                'evaluations' => $sortedEvals,
                'total' => $evals->count(),
                'retained' => $evals->where('decision', EvaluationDecision::Retained)->count(),
                'probation' => $evals->where('decision', EvaluationDecision::ProbationNeeded)->count(),
                'not_retained' => $evals->where('decision', EvaluationDecision::NotRetained)->count(),
            ];
        }

        $pdf = Pdf::loadView('pdf.comite-minutes', [
            'session' => $session,
            'groupsData' => $groupsData,
        ])->setPaper('a4', 'landscape');

        $fileName = sprintf(
            'PV_Comite_CA_Sion_%s.pdf',
            str_replace(' ', '_', $session->title)
        );

        return response()->streamDownload(
            fn () => print ($pdf->output()),
            $fileName,
            ['Content-Type' => 'application/pdf']
        );
    }
}
