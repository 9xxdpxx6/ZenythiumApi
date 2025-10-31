<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

final class WorkoutSetFilter extends BaseFilter
{
    public function apply(Builder $query): Builder
    {
        $this->applySearchFilter($query);
        $this->applyWorkoutFilter($query);
        $this->applyPlanExerciseFilter($query);
        $this->applyWeightRangeFilter($query);
        $this->applyRepsRangeFilter($query);
        $this->applyUserFilter($query);
        $this->applySortingFilter($query);

        return $query;
    }

    private function applySearchFilter(Builder $query): void
    {
        if ($this->hasFilter('search')) {
            $searchTerm = $this->getFilter('search');
            $query->where(function ($q) use ($searchTerm): void {
                $this->applySmartSearchInRelation($q, 'workout.plan', ['name'], $searchTerm);
                $this->applySmartSearchInRelationOr($q, 'planExercise.exercise', ['name'], $searchTerm);
                $this->applySmartSearchInRelationOr($q, 'workout.user', ['name'], $searchTerm);
            });
        }
    }

    private function applyWorkoutFilter(Builder $query): void
    {
        if ($this->hasFilter('workout_id')) {
            $query->where('workout_id', $this->getFilter('workout_id'));
        }
    }

    private function applyPlanExerciseFilter(Builder $query): void
    {
        if ($this->hasFilter('plan_exercise_id')) {
            $query->where('plan_exercise_id', $this->getFilter('plan_exercise_id'));
        }
    }

    private function applyWeightRangeFilter(Builder $query): void
    {
        if ($this->hasFilter('weight_from')) {
            $query->where('weight', '>=', $this->getFilter('weight_from'));
        }

        if ($this->hasFilter('weight_to')) {
            $query->where('weight', '<=', $this->getFilter('weight_to'));
        }

        if ($this->hasFilter('weight_min')) {
            $query->where('weight', '>=', $this->getFilter('weight_min'));
        }

        if ($this->hasFilter('weight_max')) {
            $query->where('weight', '<=', $this->getFilter('weight_max'));
        }
    }

    private function applyRepsRangeFilter(Builder $query): void
    {
        if ($this->hasFilter('reps_from')) {
            $query->where('reps', '>=', $this->getFilter('reps_from'));
        }

        if ($this->hasFilter('reps_to')) {
            $query->where('reps', '<=', $this->getFilter('reps_to'));
        }

        if ($this->hasFilter('reps_min')) {
            $query->where('reps', '>=', $this->getFilter('reps_min'));
        }

        if ($this->hasFilter('reps_max')) {
            $query->where('reps', '<=', $this->getFilter('reps_max'));
        }
    }

    private function applyUserFilter(Builder $query): void
    {
        if ($this->hasFilter('user_id')) {
            $query->whereHas('workout', function ($q) {
                $q->where('user_id', $this->getFilter('user_id'));
            });
        }
    }

    private function applySortingFilter(Builder $query): void
    {
        parent::applySorting($query, 'created_at', 'desc');
    }
}
