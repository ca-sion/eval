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
            self::Collective => 'Session d\'évaluation ('.$this->regulationArticle().')',
            self::Adaptation => 'Période d\'adaptation ('.$this->regulationArticle().')',
            self::EvaluationProbation => 'Sursis probatoire ('.$this->regulationArticle().')',
            self::DisciplinaryProbation => 'Mise à l\'épreuve disciplinaire ('.$this->regulationArticle().')',
        };
    }

    /**
     * Référence de l'article dans le Règlement du club.
     */
    public function regulationArticle(): string
    {
        return match ($this) {
            self::Collective => 'art. 10.2',
            self::Adaptation => 'art. 3.4 et 10.2',
            self::EvaluationProbation => 'art. 10.5',
            self::DisciplinaryProbation => 'art. 27.1',
        };
    }

    /**
     * Description détaillée du contexte pour les athlètes et les familles.
     */
    public function description(): string
    {
        return match ($this) {
            self::Collective => 'Session semestrielle officielle menée auprès de l\'ensemble des athlètes actifs du club dans leurs groupes d\'entraînement respectifs.',
            self::Adaptation => 'Période probatoire de 5 semaines pour tout nouvel athlète intégrant le club afin de valider son adéquation avec le groupe, les exigences et l\'esprit du club.',
            self::EvaluationProbation => 'Période complémentaire de 2 semaines accordée à un athlète en difficulté pour lui permettre de corriger des fragilités avec l\'accompagnement de son entraîneur.',
            self::DisciplinaryProbation => 'Période ciblée de 2 semaines consécutive à un rappel au règlement pour observer le rétablissement d\'une attitude irréprochable.',
        };
    }

    /**
     * Libellé du statut d'athlète associé à ce contexte.
     */
    public function associatedStatusLabel(): string
    {
        return match ($this) {
            self::Collective => 'Membre actif',
            self::Adaptation => 'Adaptation',
            self::EvaluationProbation, self::DisciplinaryProbation => 'Sursis probatoire',
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
