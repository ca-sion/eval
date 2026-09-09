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
            self::Cantonal => 6.0,
            self::Regional => 7.5,
            self::National => 9.0,
            self::International => 10.0,
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
