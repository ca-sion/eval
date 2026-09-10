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
        foreach ($criteria as $criterion) {
            $weights[$criterion->value] = (int) round($criterion->defaultWeight() * 100);
        }

        return view('public.guide', [
            'criteria' => $criteria,
            'qualitativeTiers' => $qualitativeTiers,
            'qualitativeRubric' => $qualitativeRubric,
            'contexts' => $contexts,
            'decisions' => $decisions,
            'levels' => $levels,
            'weights' => $weights,
        ]);
    }
}
