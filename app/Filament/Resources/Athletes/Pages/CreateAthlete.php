<?php

namespace App\Filament\Resources\Athletes\Pages;

use App\Enums\AthleteStatus;
use App\Enums\EvaluationContext;
use App\Filament\Resources\Athletes\AthleteResource;
use App\Models\Evaluation;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateAthlete extends CreateRecord
{
    protected static string $resource = AthleteResource::class;

    protected function afterCreate(): void
    {
        $startAdaptation = $this->data['start_adaptation'] ?? false;

        if ($startAdaptation) {
            $athlete = $this->record;
            $group = $athlete->group;
            $weeksCount = (int) config('evaluation.durations.adaptation_weeks', 5);

            $athlete->update(['status' => AthleteStatus::Adaptation]);

            Evaluation::create([
                'athlete_id' => $athlete->id,
                'group_id' => $athlete->group_id,
                'context' => EvaluationContext::Adaptation,
                'start_date' => Carbon::today(),
                'end_date' => Carbon::today()->addWeeks($weeksCount),
                'weeks_count' => $weeksCount,
                'sessions_per_week' => $group?->default_sessions_per_week ?? 3,
                'competitions_planned' => $group?->default_competitions_planned ?? 6,
            ]);
        }
    }
}
