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
            self::Collective, self::Adaptation => 5,
            self::EvaluationProbation, self::DisciplinaryProbation => 2,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Collective => 'Période d\'évaluation',
            self::Adaptation => 'Période d\'adaptation (Art. 3.4 et 10.2)',
            self::EvaluationProbation => 'Sursis probatoire de sélection (Art. 10.5)',
            self::DisciplinaryProbation => 'Sursis disciplinaire (Art. 27.1)',
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
            self::EvaluationProbation => 'Eval.',
            self::DisciplinaryProbation => 'Sursis',
        };
    }
}
