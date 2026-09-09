<?php

namespace App\Livewire;

use App\Enums\AthleticLevel;
use App\Enums\EvaluationContext;
use App\Enums\EvaluationStatus;
use App\Models\Evaluation;
use App\Models\Group;
use App\Services\EvaluationCalculatorService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CoachGroupEvaluation extends Component
{
    public Group $group;

    public function mount(Group $group): void
    {
        $this->group = $group;
    }

    public function incrementLateness(int $evaluationId, EvaluationCalculatorService $calculator): void
    {
        $evaluation = $this->getEditableEvaluation($evaluationId);
        if (! $evaluation) {
            return;
        }

        $evaluation->increment('lateness_count');
        $calculator->calculateAthlete($evaluation);
    }

    public function decrementLateness(int $evaluationId, EvaluationCalculatorService $calculator): void
    {
        $evaluation = $this->getEditableEvaluation($evaluationId);
        if (! $evaluation) {
            return;
        }

        if ($evaluation->lateness_count > 0) {
            $evaluation->decrement('lateness_count');
            $calculator->calculateAthlete($evaluation);
        }
    }

    public function toggleInjury(int $evaluationId, EvaluationCalculatorService $calculator): void
    {
        $evaluation = $this->getEditableEvaluation($evaluationId);
        if (! $evaluation) {
            return;
        }

        $evaluation->is_injured = ! $evaluation->is_injured;
        $evaluation->save();
        $calculator->calculateAthlete($evaluation);
    }

    public function setScore(int $evaluationId, string $field, ?float $value, EvaluationCalculatorService $calculator): void
    {
        $allowedFields = ['c4_commitment', 'c5_behavior', 'c7_progress', 'c8_sports_hygiene'];
        if (! in_array($field, $allowedFields, true)) {
            return;
        }

        $evaluation = $this->getEditableEvaluation($evaluationId);
        if (! $evaluation) {
            return;
        }

        if ($value !== null) {
            $value = min(10.0, max(0.0, round($value, 1)));
        }

        $evaluation->{$field} = $value;
        $evaluation->save();
        $calculator->calculateAthlete($evaluation);
    }

    public function setLevel(int $evaluationId, ?string $level, EvaluationCalculatorService $calculator): void
    {
        $evaluation = $this->getEditableEvaluation($evaluationId);
        if (! $evaluation) {
            return;
        }

        $evaluation->c6_level = $level ? AthleticLevel::tryFrom($level) : null;
        $evaluation->save();
        $calculator->calculateAthlete($evaluation);
    }

    public function updateNotes(int $evaluationId, ?string $notes): void
    {
        $evaluation = $this->getEditableEvaluation($evaluationId);
        if (! $evaluation) {
            return;
        }

        $evaluation->coach_notes = $notes;
        $evaluation->save();
    }

    public function toggleStatus(int $evaluationId): void
    {
        $evaluation = $this->group->evaluations()
            ->with('session')
            ->find($evaluationId);

        if (! $evaluation) {
            return;
        }

        // Permet de basculer entre Brouillon et Transmis si la session n'est pas fermée et dans les dates
        $today = Carbon::today();
        $inTime = $today->betweenIncluded($evaluation->start_date, $evaluation->end_date);
        $notClosed = ! ($evaluation->session && $evaluation->session->is_closed);

        if (! $inTime || ! $notClosed) {
            return;
        }

        $evaluation->status = ($evaluation->status === EvaluationStatus::Submitted)
            ? EvaluationStatus::Draft
            : EvaluationStatus::Submitted;

        $evaluation->save();
    }

    protected function getEditableEvaluation(int $id): ?Evaluation
    {
        /** @var Evaluation|null $evaluation */
        $evaluation = $this->group->evaluations()
            ->with('session')
            ->find($id);

        if (! $evaluation || ! $evaluation->isEditable()) {
            return null;
        }

        return $evaluation;
    }

    public function render(): View
    {
        $today = Carbon::today();

        // Récupérer toutes les évaluations actives pour ce groupe aujourd'hui
        $evaluations = $this->group->evaluations()
            ->with(['athlete', 'session'])
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->get()
            ->sortBy(fn (Evaluation $eval) => $eval->athlete->last_name.' '.$eval->athlete->first_name);

        $hasCollectiveSession = $evaluations->contains(fn (Evaluation $e) => $e->context === EvaluationContext::Collective);

        return view('livewire.coach-group-evaluation', [
            'evaluations' => $evaluations,
            'hasCollectiveSession' => $hasCollectiveSession,
            'today' => $today,
        ])->layout('layouts.coach', ['group' => $this->group]);
    }
}
