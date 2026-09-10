<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EvaluationContext: string implements HasColor, HasLabel
{
    case Collective = 'collective';
    case Adaptation = 'adaptation';
    case EvaluationProbation = 'evaluation_probation';
    case DisciplinaryProbation = 'disciplinary_probation';

    public function defaultWeeks(): int
    {
        return match ($this) {
            self::Collective => (int) config('evaluation.durations.collective_session_weeks', 5),
            self::Adaptation => (int) config('evaluation.durations.adaptation_weeks', 5),
            self::EvaluationProbation => (int) config('evaluation.durations.evaluation_probation_weeks', 2),
            self::DisciplinaryProbation => (int) config('evaluation.durations.disciplinary_probation_weeks', 2),
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Collective => 'Session d\'évaluation (art. 10.2)',
            self::Adaptation => 'Période d\'adaptation (art. 3.4 et 10.2)',
            self::EvaluationProbation => 'Sursis probatoire (art. 10.5)',
            self::DisciplinaryProbation => 'Mise à l\'épreuve disciplinaire (art. 27.1)',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Collective => 'info',
            self::Adaptation => 'warning',
            self::EvaluationProbation => 'danger',
            self::DisciplinaryProbation => 'danger',
        };
    }

    /**
     * Libellé court et condensé pour les en-têtes de tableaux et badges.
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Collective => 'Eval.',
            self::Adaptation => 'Adapt.',
            self::EvaluationProbation => 'Sursis',
            self::DisciplinaryProbation => 'Sursis',
        };
    }
}
