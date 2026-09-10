<?php

namespace App\Http\Controllers;

use App\Enums\AthleticLevel;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationCriterion;
use App\Enums\EvaluationDecision;
use Illuminate\Contracts\View\View;

class PublicEvaluationGuideController extends Controller
{
    /**
     * Affiche la page publique explicative des évaluations destinée aux athlètes et aux parents.
     */
    public function __invoke(): View
    {
        $criteria = EvaluationCriterion::cases();
        $qualitativeTiers = EvaluationCriterion::qualitativeTiers();
        $qualitativeRubric = EvaluationCriterion::qualitativeRubric();
        $contexts = EvaluationContext::cases();
        $decisions = EvaluationDecision::cases();
        $levels = AthleticLevel::cases();

        $weights = [];
        $weightsDecimal = [];
        foreach ($criteria as $criterion) {
            $weights[$criterion->value] = (int) round($criterion->defaultWeight() * 100);
            $weightsDecimal[$criterion->value] = (float) $criterion->defaultWeight();
        }

        $simulatorConfig = [
            'weights' => $weightsDecimal,
            'penalties' => config('evaluation.penalties', [
                'retard_base_score' => 6.0,
                'retard_deduction' => 0.3,
            ]),
            'bonuses' => config('evaluation.bonuses', [
                'club_engagement' => 0.75,
            ]),
            'defaults' => config('evaluation.defaults', [
                'sessions_per_week' => 2,
                'competitions_planned' => 3,
                'min_score' => 5.0,
                'quota_places' => 20,
                'max_volunteering_age' => 17,
                'required_volunteering_count' => 2,
            ]),
            'levels' => array_map(fn (AthleticLevel $l) => [
                'key' => $l->value,
                'label' => $l->getLabel(),
                'score' => $l->score(),
            ], $levels),
            'minScore' => (float) config('evaluation.defaults.min_score', 5.0),
        ];

        return view('public.guide', [
            'criteria' => $criteria,
            'qualitativeTiers' => $qualitativeTiers,
            'qualitativeRubric' => $qualitativeRubric,
            'contexts' => $contexts,
            'decisions' => $decisions,
            'levels' => $levels,
            'weights' => $weights,
            'simulatorConfig' => $simulatorConfig,
        ]);
    }
}
