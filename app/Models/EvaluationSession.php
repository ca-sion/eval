<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluationSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'weeks_count' => 'integer',
            'is_closed' => 'boolean',
            'last_tiiva_synced_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (EvaluationSession $session): void {
            if ($session->wasChanged(['start_date', 'end_date', 'weeks_count'])) {
                $session->evaluations()->update([
                    'start_date' => $session->start_date,
                    'end_date' => $session->end_date,
                    'weeks_count' => $session->weeks_count,
                ]);
            }
        });
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }
}
