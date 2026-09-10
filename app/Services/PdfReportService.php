<?php

namespace App\Services;

use App\Enums\EvaluationDecision;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class PdfReportService
{
    /**
     * Génère et diffuse le flux PDF du bilan individuel d'évaluation.
     */
    public function generateInterviewReport(Evaluation $evaluation): Response
    {
        $evaluation->loadMissing(['athlete', 'group', 'session']);

        $pdf = Pdf::loadView('pdf.interview-sheet', [
            'evaluation' => $evaluation,
        ])->setPaper('a4', 'portrait');

        $fileName = sprintf(
            'bilan-evaluation-%s-%s.pdf',
            str($evaluation->athlete->last_name)->slug('_', 'fr'),
            $evaluation->start_date ? $evaluation->start_date->format('Ym') : date('Ym')
        );

        return $pdf->stream($fileName);
    }

    /**
     * Génère et diffuse le flux PDF du Procès-Verbal officiel destiné au Comité du club.
     */
    public function generateOfficialSessionMinutes(EvaluationSession $session): Response
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
            'PV-Evaluations-%s.pdf',
            str($session->title)->slug('_', 'fr')
        );

        return $pdf->stream($fileName);
    }
}
