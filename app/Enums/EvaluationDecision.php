<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum EvaluationDecision: string implements HasColor, HasLabel
{
    case Retained = 'retained';
    case ProbationNeeded = 'probation_needed';
    case NotRetained = 'not_retained';
    case Pending = 'pending';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Retained => 'Retenu',
            self::ProbationNeeded => 'Sursis probatoire (2 sem.)',
            self::NotRetained => 'Non retenu',
            self::Pending => 'En attente',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Retained => 'success',
            self::ProbationNeeded => 'warning',
            self::NotRetained => 'danger',
            self::Pending => 'gray',
        };
    }
}
