<?php

namespace App\Models;

use App\Enums\AthleticLevel;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationDecision;
use App\Enums\EvaluationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Evaluation extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'context' => EvaluationContext::class,
            'status' => EvaluationStatus::class,
            'c6_level' => AthleticLevel::class,
            'decision' => EvaluationDecision::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'weeks_count' => 'integer',
            'sessions_per_week' => 'integer',
            'competitions_planned' => 'integer',
            'competitions_done' => 'integer',
            'real_attendances' => 'integer',
            'parent_volunteering_count' => 'integer',
            'has_club_engagement' => 'boolean',
            'is_injured' => 'boolean',
            'lateness_count' => 'integer',
            'c4_commitment' => 'float',
            'c5_behavior' => 'float',
            'c7_progress' => 'float',
            'c8_environment' => 'float',
            'c1_score' => 'float',
            'c2_score' => 'float',
            'c3_score' => 'float',
            'c6_score' => 'float',
            'c9_score' => 'float',
            'base_average' => 'float',
            'bonus_points' => 'float',
            'final_score' => 'float',
            'rank' => 'integer',
        ];
    }

    public function athlete(): BelongsTo
    {
        return $this->belongsTo(Athlete::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(EvaluationSession::class, 'evaluation_session_id');
    }

    public function parentEvaluation(): BelongsTo
    {
        return $this->belongsTo(Evaluation::class, 'parent_evaluation_id');
    }

    public function probationEvaluations(): HasMany
    {
        return $this->hasMany(Evaluation::class, 'parent_evaluation_id');
    }

    public function isEditable(): bool
    {
        return $this->canToggleStatus() && $this->status !== EvaluationStatus::Submitted;
    }

    public function canToggleStatus(): bool
    {
        $today = Carbon::today();

        if ($this->session && $this->session->is_closed) {
            return false;
        }

        $startDate = $this->session?->start_date ?? $this->start_date;
        $endDate = $this->session?->end_date ?? $this->end_date;

        if (! $startDate || ! $endDate) {
            return false;
        }

        return $today->betweenIncluded($startDate, $endDate);
    }

    public function currentWeekNumber(): int
    {
        $today = Carbon::today();
        $startDate = $this->session?->start_date ?? $this->start_date;
        $weeksCount = $this->session?->weeks_count ?? $this->weeks_count;

        if (! $startDate) {
            return 1;
        }

        if ($today->lt($startDate)) {
            return 1;
        }

        $diffDays = $startDate->diffInDays($today);
        $week = (int) floor($diffDays / 7) + 1;

        return min($week, (int) ($weeksCount ?: 5));
    }
}
