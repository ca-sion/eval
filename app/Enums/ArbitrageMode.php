<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum ArbitrageMode: string implements HasLabel
{
    case Quota = 'quota';
    case Threshold = 'threshold';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::Quota => 'Quota (places fixes)',
            self::Threshold => 'Threshold (note minimale)',
        };
    }
}
