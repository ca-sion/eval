<?php

namespace App\Models;

use App\Enums\AthleteStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Athlete extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status' => AthleteStatus::class,
            'birth_year' => 'integer',
            'birthday' => 'date',
            'entry_date' => 'date',
            'guardian_tiiva_ids' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Athlete $athlete): void {
            if ($athlete->birthday && ! $athlete->birth_year) {
                $athlete->birth_year = (int) $athlete->birthday->format('Y');
            }
        });
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function evaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => "{$this->first_name} {$this->last_name}"
        );
    }
}
