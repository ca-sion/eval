<?php

namespace App\Services;

use App\Enums\ArbitrageMode;
use App\Enums\EvaluationCriterion;
use App\Enums\EvaluationDecision;
use App\Models\Evaluation;
use App\Models\EvaluationSession;
use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class EvaluationCalculatorService
{
    /**
     * Calcule tous les critères élémentaires, moyennes et note finale d'une évaluation individuelle.
     */
    public function calculateAthlete(Evaluation $evaluation, bool $save = true): Evaluation
    {
        $evaluation->loadMissing(['athlete', 'group', 'session']);

        $group = $evaluation->group;
        $athlete = $evaluation->athlete;
        $startDate = $evaluation->start_date instanceof Carbon ? $evaluation->start_date : Carbon::parse($evaluation->start_date);

        $sumWeights = 0.0;
        $sumWeightedScores = 0.0;

        foreach (EvaluationCriterion::cases() as $criterion) {
            $score = $criterion->calculateScore($evaluation);
            $evaluation->{$criterion->scoreColumn()} = $score;

            if ($score !== null) {
                $weight = $criterion->defaultWeight();
                $sumWeights += $weight;
                $sumWeightedScores += ($score * $weight);
            }
        }

        // Garde-fou technique : si aucun critère noté
        if ($sumWeights == 0.0) {
            $evaluation->base_average = null;
            $evaluation->bonus_points = null;
            $evaluation->final_score = null;
        } else {
            $baseAverage = round($sumWeightedScores / $sumWeights, 2);
            $evaluation->base_average = $baseAverage;

            $bonusPoints = $evaluation->has_club_engagement
                ? (float) config('evaluation.bonuses.club_engagement', 0.75)
                : 0.0;
            $evaluation->bonus_points = $bonusPoints;

            $finalScore = min(10.00, round($baseAverage + $bonusPoints, 2));
            $evaluation->final_score = $finalScore;
        }

        if ($save) {
            $evaluation->save();
        }

        return $evaluation;
    }

    /**
     * Recalcule et arbitre un groupe complet (calcul des rangs et des décisions de sélection).
     *
     * @param  Collection<int, Evaluation>|null  $evaluations
     * @return Collection<int, Evaluation>
     */
    public function arbitrateGroup(Group $group, ?EvaluationSession $session = null, ?Collection $evaluations = null): Collection
    {
        if ($evaluations === null) {
            $query = Evaluation::query()->where('group_id', $group->id);
            if ($session !== null) {
                $query->where('evaluation_session_id', $session->id);
            }
            $evaluations = $query->with(['athlete', 'group'])->get();
        }

        // 1. Calcul individuel pour chaque athlète
        foreach ($evaluations as $evaluation) {
            $this->calculateAthlete($evaluation, save: false);
        }

        // 2. Classement par note finale décroissante (les notes nulles vont en fin de liste)
        $sorted = $evaluations->sort(function (Evaluation $a, Evaluation $b) {
            if ($a->final_score === null && $b->final_score === null) {
                return 0;
            }
            if ($a->final_score === null) {
                return 1;
            }
            if ($b->final_score === null) {
                return -1;
            }

            return $b->final_score <=> $a->final_score;
        })->values();

        $mode = $group->arbitration_mode ?? ArbitrageMode::Quota;
        $quota = (int) ($group->quota_places ?? config('evaluation.defaults.quota_places', 12));
        $minScore = (float) ($group->min_score ?? config('evaluation.defaults.min_score', 6.50));

        $rank = 1;
        foreach ($sorted as $evaluation) {
            if ($evaluation->final_score === null) {
                $evaluation->rank = null;
                $evaluation->decision = EvaluationDecision::Pending;
            } else {
                $evaluation->rank = $rank;

                if ($mode === ArbitrageMode::Quota) {
                    if ($rank <= $quota) {
                        $evaluation->decision = EvaluationDecision::Retained;
                    } else {
                        $evaluation->decision = ($evaluation->final_score >= 6.0)
                            ? EvaluationDecision::ProbationNeeded
                            : EvaluationDecision::NotRetained;
                    }
                } else {
                    // Mode Threshold
                    if ($evaluation->final_score >= $minScore) {
                        $evaluation->decision = EvaluationDecision::Retained;
                    } else {
                        $evaluation->decision = ($evaluation->final_score >= 6.0)
                            ? EvaluationDecision::ProbationNeeded
                            : EvaluationDecision::NotRetained;
                    }
                }

                $rank++;
            }

            $evaluation->save();
        }

        return $sorted;
    }
}
