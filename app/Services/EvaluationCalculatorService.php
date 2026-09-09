<?php

namespace App\Services;

use App\Enums\ArbitrageMode;
use App\Enums\AthleticLevel;
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

        // 1. C1 : Assiduité (Poids 20%)
        $c1 = null;
        if (! $evaluation->is_injured && $evaluation->real_attendances !== null) {
            $expectedAttendances = $evaluation->sessions_per_week * $evaluation->weeks_count;
            if ($expectedAttendances > 0) {
                $c1 = min(10.0, max(0.0, ($evaluation->real_attendances / $expectedAttendances) * 10.0));
                $c1 = round($c1, 2);
            }
        }
        $evaluation->c1_score = $c1;

        // 2. C2 : Ponctualité (Poids 5%)
        $retardDeduction = (float) config('evaluation.penalties.retard_deduction', 1.5);
        $c2 = max(0.0, 10.0 - ($evaluation->lateness_count * $retardDeduction));
        $evaluation->c2_score = round($c2, 2);

        // 3. C3 : Compétitions (Poids 15%)
        $c3 = null;
        if (! $evaluation->is_injured) {
            if ($evaluation->competitions_planned > 0) {
                $c3 = min(10.0, max(0.0, ($evaluation->competitions_done / $evaluation->competitions_planned) * 10.0));
                $c3 = round($c3, 2);
            }
        }
        $evaluation->c3_score = $c3;

        // 4. C4 : Implication (Poids 15%)
        $c4 = $evaluation->c4_commitment !== null ? (float) $evaluation->c4_commitment : null;

        // 5. C5 : Comportement (Poids 15%)
        $c5 = $evaluation->c5_behavior !== null ? (float) $evaluation->c5_behavior : null;

        // 6. C6 : Niveau athlétique (Poids 10%)
        $c6 = null;
        if ($evaluation->c6_level !== null) {
            $levelEnum = $evaluation->c6_level instanceof AthleticLevel
                ? $evaluation->c6_level
                : AthleticLevel::tryFrom($evaluation->c6_level);

            if ($levelEnum !== null) {
                $c6 = $levelEnum->score();
            }
        }
        $evaluation->c6_score = $c6;

        // 7. C7 : Progression (Poids 10%)
        $c7 = $evaluation->c7_progress !== null ? (float) $evaluation->c7_progress : null;

        // 8. C8 : Hygiène et environnement (Poids 5%)
        $c8 = $evaluation->c8_sports_hygiene !== null ? (float) $evaluation->c8_sports_hygiene : null;

        // 9. C9 : Bénévolat des parents (Poids 5%)
        $c9 = null;
        $maxVolunteeringAge = $group ? $group->max_volunteering_age : (int) config('evaluation.defaults.max_volunteering_age', 14);
        $athleteAgeAtSession = $athlete ? ($startDate->year - (int) $athlete->birth_year) : 0;

        if ($athlete && $athleteAgeAtSession <= $maxVolunteeringAge) {
            $requiredVolunteering = $group ? $group->required_volunteering_count : (int) config('evaluation.defaults.required_volunteering_count', 2);
            if ($requiredVolunteering > 0) {
                $count = (int) $evaluation->parent_volunteering_count;
                if ($count >= $requiredVolunteering) {
                    $c9 = min(10.0, 7.5 + ($count - $requiredVolunteering) * 1.25);
                } else {
                    $c9 = max(0.0, ($count / $requiredVolunteering) * 5.0);
                }
                $c9 = round($c9, 2);
            }
        }
        $evaluation->c9_score = $c9;

        // Map des critères et de leurs pondérations configurées
        $criteria = [
            'c1' => ['score' => $c1, 'weight' => (float) config('evaluation.weights.c1', 0.20)],
            'c2' => ['score' => $c2, 'weight' => (float) config('evaluation.weights.c2', 0.05)],
            'c3' => ['score' => $c3, 'weight' => (float) config('evaluation.weights.c3', 0.15)],
            'c4' => ['score' => $c4, 'weight' => (float) config('evaluation.weights.c4', 0.15)],
            'c5' => ['score' => $c5, 'weight' => (float) config('evaluation.weights.c5', 0.15)],
            'c6' => ['score' => $c6, 'weight' => (float) config('evaluation.weights.c6', 0.10)],
            'c7' => ['score' => $c7, 'weight' => (float) config('evaluation.weights.c7', 0.10)],
            'c8' => ['score' => $c8, 'weight' => (float) config('evaluation.weights.c8', 0.05)],
            'c9' => ['score' => $c9, 'weight' => (float) config('evaluation.weights.c9', 0.05)],
        ];

        // Redistribution dynamique des pondérations
        $sumWeights = 0.0;
        $sumWeightedScores = 0.0;

        foreach ($criteria as $criterion) {
            if ($criterion['score'] !== null) {
                $sumWeights += $criterion['weight'];
                $sumWeightedScores += ($criterion['score'] * $criterion['weight']);
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
