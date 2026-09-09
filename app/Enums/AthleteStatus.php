<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum AthleteStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Adaptation = 'adaptation';
    case Probation = 'probation';
    case Inactive = 'inactive';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Adaptation => 'Période d\'adaptation',
            self::Probation => 'En sursis probatoire',
            self::Inactive => 'Inactif',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Active => 'success',
            self::Adaptation => 'warning',
            self::Probation => 'danger',
            self::Inactive => 'gray',
        };
    }
}
