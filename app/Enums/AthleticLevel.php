<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum AthleticLevel: string implements HasLabel
{
    case Cantonal = 'cantonal';
    case Regional = 'regional';
    case National = 'national';
    case International = 'international';

    public function score(): float
    {
        return match ($this) {
            self::Cantonal => 5.5,
            self::Regional => 6.0,
            self::National => 7.0,
            self::International => 9.0,
        };
    }

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Cantonal => 'Cantonal',
            self::Regional => 'Régional',
            self::National => 'National',
            self::International => 'International',
        };
    }
}
