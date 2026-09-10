<?php

namespace App\Services;

use App\Enums\ArbitrageMode;
use App\Enums\AthleteStatus;
use App\Enums\EvaluationCriterion;
use App\Enums\EvaluationDecision;
use App\Enums\EvaluationStatus;
use App\Models\Athlete;
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

    /**
     * Calcule l'état d'avancement complet et les métriques de workflow pour une session donnée.
     *
     * @return array<string, mixed>
     */
    public function getSessionProgressStats(EvaluationSession $session): array
    {
        $evaluations = $session->evaluations()->with(['athlete', 'group'])->get();
        $totalActiveAthletes = Athlete::where('status', AthleteStatus::Active)->count();
        $totalEvaluations = $evaluations->count();

        // 1. Métriques de saisie entraîneur (C4, C5, C6, C7, C8)
        $fullyRatedCount = 0;
        $totalRatingPoints = 0;
        $maxPossibleRatingPoints = max(1, $totalEvaluations * 5);

        foreach ($evaluations as $eval) {
            $ratedCriteria = 0;
            if ($eval->c4_commitment !== null) {
                $ratedCriteria++;
            }
            if ($eval->c5_behavior !== null) {
                $ratedCriteria++;
            }
            if ($eval->c6_level !== null) {
                $ratedCriteria++;
            }
            if ($eval->c7_progress !== null) {
                $ratedCriteria++;
            }
            if ($eval->c8_environment !== null) {
                $ratedCriteria++;
            }

            $totalRatingPoints += $ratedCriteria;
            if ($ratedCriteria === 5) {
                $fullyRatedCount++;
            }
        }

        $coachProgressPercent = $totalEvaluations > 0
            ? (int) round(($totalRatingPoints / $maxPossibleRatingPoints) * 100)
            : 0;

        // 2. Métriques NDS (Présences C1)
        $ndsSyncedCount = $evaluations->whereNotNull('real_attendances')->count();
        $ndsProgressPercent = $totalEvaluations > 0
            ? (int) round(($ndsSyncedCount / $totalEvaluations) * 100)
            : 0;

        // 3. Métriques d'arbitrage
        $arbitratedCount = $evaluations->filter(fn (Evaluation $e) => $e->final_score !== null && $e->decision !== null && $e->decision !== EvaluationDecision::Pending)->count();
        $retainedCount = $evaluations->where('decision', EvaluationDecision::Retained)->count();
        $probationCount = $evaluations->where('decision', EvaluationDecision::ProbationNeeded)->count();
        $notRetainedCount = $evaluations->where('decision', EvaluationDecision::NotRetained)->count();
        $pendingCount = $totalEvaluations - ($retainedCount + $probationCount + $notRetainedCount);

        // 4. Statistiques par groupe
        $groups = Group::whereHas('evaluations', fn ($q) => $q->where('evaluation_session_id', $session->id))
            ->orWhereHas('athletes', fn ($q) => $q->where('status', AthleteStatus::Active))
            ->distinct()
            ->get();

        $groupStats = [];
        foreach ($groups as $group) {
            $groupEvals = $evaluations->where('group_id', $group->id);
            $groupTotal = $groupEvals->count();
            $groupRated = $groupEvals->filter(fn (Evaluation $e) => $e->c4_commitment !== null && $e->c5_behavior !== null && $e->c6_level !== null && $e->c7_progress !== null && $e->c8_environment !== null)->count();
            $groupNds = $groupEvals->whereNotNull('real_attendances')->count();
            $groupPercent = $groupTotal > 0 ? (int) round(($groupRated / $groupTotal) * 100) : 0;
            $groupSubmitted = $groupEvals->where('status', EvaluationStatus::Submitted)->count();

            $groupStats[] = [
                'group' => $group,
                'total' => $groupTotal,
                'rated_count' => $groupRated,
                'nds_count' => $groupNds,
                'progress_percent' => $groupPercent,
                'submitted_count' => $groupSubmitted,
                'retained_count' => $groupEvals->where('decision', EvaluationDecision::Retained)->count(),
                'probation_count' => $groupEvals->where('decision', EvaluationDecision::ProbationNeeded)->count(),
                'not_retained_count' => $groupEvals->where('decision', EvaluationDecision::NotRetained)->count(),
                'mobile_url' => $group->getMobileUrl(),
                'whatsapp_url' => $group->getWhatsAppShareUrl(),
            ];
        }

        // Taux global de complétion de la session
        $globalProgress = 0;
        if ($totalEvaluations > 0) {
            // Poids : Initialisation (20%), Saisie Coach (40%), NDS (20%), Arbitrage (20%)
            $initScore = ($totalEvaluations >= $totalActiveAthletes && $totalActiveAthletes > 0) ? 20 : 10;
            $coachScore = (int) round(($coachProgressPercent / 100) * 40);
            $ndsScore = (int) round(($ndsProgressPercent / 100) * 20);
            $arbitrageScore = $totalEvaluations > 0 ? (int) round(($arbitratedCount / $totalEvaluations) * 20) : 0;
            $globalProgress = min(100, $initScore + $coachScore + $ndsScore + $arbitrageScore);
        }

        return [
            'total_active_athletes' => $totalActiveAthletes,
            'total_evaluations' => $totalEvaluations,
            'fully_rated_count' => $fullyRatedCount,
            'coach_progress_percent' => $coachProgressPercent,
            'nds_synced_count' => $ndsSyncedCount,
            'nds_progress_percent' => $ndsProgressPercent,
            'arbitrated_count' => $arbitratedCount,
            'retained_count' => $retainedCount,
            'probation_count' => $probationCount,
            'not_retained_count' => $notRetainedCount,
            'pending_count' => $pendingCount,
            'global_progress' => $globalProgress,
            'groups_stats' => $groupStats,
        ];
    }
}
