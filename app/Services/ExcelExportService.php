<?php

namespace App\Services;

use App\Enums\EvaluationCriterion;
use App\Enums\EvaluationDecision;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use Illuminate\Support\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelExportService
{
    /**
     * Exporte la session d'évaluation en classeur Excel multi-feuilles.
     */
    public function exportSession(EvaluationSession $session): BinaryFileResponse
    {
        $session->loadMissing('evaluations.athlete', 'evaluations.group');

        $tempFile = tempnam(sys_get_temp_dir(), 'ca_sion_eval_').'.xlsx';

        $writer = new Writer;
        $writer->openToFile($tempFile);

        // 1. Feuille de Synthèse Générale
        $summarySheet = $writer->getCurrentSheet();
        $summarySheet->setName('Synthèse Générale');

        $writer->addRow(Row::fromValues([
            'SESSION D\'ÉVALUATION : '.$session->title,
            'Période : '.$session->start_date->format('d.m.Y').' au '.$session->end_date->format('d.m.Y'),
            'Durée : '.$session->weeks_count.' semaines',
        ]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues([
            'Groupe',
            'Mode d\'arbitrage',
            'Effectif total',
            'Retenus',
            'Sursis probatoires',
            'Non retenus',
        ]));

        $evaluationsByGroup = $session->evaluations->groupBy('group_id');
        $groupsData = [];

        foreach ($evaluationsByGroup as $groupId => $evals) {
            $group = Group::find($groupId);
            if (! $group) {
                continue;
            }

            $total = $evals->count();
            $retained = $evals->where('decision', EvaluationDecision::Retained)->count();
            $probation = $evals->where('decision', EvaluationDecision::ProbationNeeded)->count();
            $notRetained = $evals->where('decision', EvaluationDecision::NotRetained)->count();

            $writer->addRow(Row::fromValues([
                $group->name,
                $group->arbitration_mode->getLabel(),
                $total,
                $retained,
                $probation,
                $notRetained,
            ]));

            $groupsData[] = [
                'group' => $group,
                'evaluations' => $evals->sortBy(fn (Evaluation $e) => $e->rank ?? 999)->values(),
            ];
        }

        // 2. Feuilles détaillées par groupe
        foreach ($groupsData as $data) {
            $group = $data['group'];
            /** @var Collection<int, Evaluation> $evals */
            $evals = $data['evaluations'];

            // Nettoyer le nom de la feuille (max 31 caractères, pas de caractères interdits)
            $sheetName = substr(preg_replace('/[\\\\\\/?*\\[\\]:]/', '', $group->name), 0, 31);
            $sheet = $writer->addNewSheetAndMakeItCurrent();
            $sheet->setName($sheetName);

            $writer->addRow(Row::fromValues([
                'Rang',
                'Nom',
                'Prénom',
                'Année',
                'Licence',
                'Blessé',
                'Retards',
                EvaluationCriterion::C1_Attendance->shortLabel().' ('.EvaluationCriterion::C1_Attendance->code().')',
                EvaluationCriterion::C2_Punctuality->shortLabel().' ('.EvaluationCriterion::C2_Punctuality->code().')',
                EvaluationCriterion::C3_Competitions->shortLabel().' ('.EvaluationCriterion::C3_Competitions->code().')',
                EvaluationCriterion::C4_Commitment->shortLabel().' ('.EvaluationCriterion::C4_Commitment->code().')',
                EvaluationCriterion::C5_Behavior->shortLabel().' ('.EvaluationCriterion::C5_Behavior->code().')',
                EvaluationCriterion::C6_Performance->shortLabel().' ('.EvaluationCriterion::C6_Performance->code().')',
                EvaluationCriterion::C7_Progress->shortLabel().' ('.EvaluationCriterion::C7_Progress->code().')',
                EvaluationCriterion::C8_Environment->shortLabel().' ('.EvaluationCriterion::C8_Environment->code().')',
                EvaluationCriterion::C9_Volunteering->shortLabel().' ('.EvaluationCriterion::C9_Volunteering->code().')',
                'Moyenne base',
                'Bonus engagement',
                'Note finale',
                'Décision',
                'Notes entraîneur',
            ]));

            foreach ($evals as $eval) {
                $writer->addRow(Row::fromValues([
                    $eval->rank ?? '-',
                    $eval->athlete->last_name,
                    $eval->athlete->first_name,
                    $eval->athlete->birth_year,
                    $eval->athlete->license_number ?? '',
                    $eval->is_injured ? 'OUI' : 'NON',
                    $eval->lateness_count,
                    $eval->c1_score !== null ? number_format($eval->c1_score, 2) : ($eval->is_injured ? 'Blessé' : '-'),
                    $eval->c2_score !== null ? number_format($eval->c2_score, 2) : '-',
                    $eval->c3_score !== null ? number_format($eval->c3_score, 2) : ($eval->is_injured ? 'Blessé' : '-'),
                    $eval->c4_commitment !== null ? number_format($eval->c4_commitment, 1) : '-',
                    $eval->c5_behavior !== null ? number_format($eval->c5_behavior, 1) : '-',
                    $eval->c6_score !== null ? number_format($eval->c6_score, 2) : '-',
                    $eval->c7_progress !== null ? number_format($eval->c7_progress, 1) : '-',
                    $eval->c8_environment !== null ? number_format($eval->c8_environment, 1) : '-',
                    $eval->c9_score !== null ? number_format($eval->c9_score, 2) : '-',
                    $eval->base_average !== null ? number_format($eval->base_average, 2) : '-',
                    $eval->has_club_engagement ? (float) config('evaluation.bonuses.club_engagement') : '0.00',
                    $eval->final_score !== null ? number_format($eval->final_score, 2) : '-',
                    $eval->decision->getLabel(),
                    $eval->coach_notes ?? '',
                ]));
            }
        }

        $writer->close();

        $fileName = sprintf(
            'Export_Session_CA_Sion_%s.xlsx',
            str_replace(' ', '_', $session->title)
        );

        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
}
