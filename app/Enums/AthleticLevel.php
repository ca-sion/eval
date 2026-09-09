<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AthleticLevel: string implements HasLabel
{
    case Regional = 'Regional';
    case Romand = 'Romand';
    case National = 'National';
    case International = 'International';

    public function score(): float
    {
        return match ($this) {
            self::Regional => 6.0,
            self::Romand => 7.5,
            self::National => 9.0,
            self::International => 10.0,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Regional => 'Régional',
            self::Romand => 'Romand',
            self::National => 'National',
            self::International => 'International',
        };
    }
}
