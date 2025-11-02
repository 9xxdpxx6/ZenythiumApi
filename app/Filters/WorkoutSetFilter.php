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
            $words = array_filter(
                array_map('trim', explode(' ', $searchTerm)),
                fn(string $word): bool => mb_strlen($word) >= 2
            );
            
            if (empty($words)) {
                return;
            }
            
            $query->where(function ($q) use ($words): void {
                // Поиск по имени плана через workout.plan
                $q->whereHas('workout.plan', function ($relationQuery) use ($words): void {
                    $relationQuery->where(function ($fieldQuery) use ($words): void {
                        foreach ($words as $word) {
                            $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                            $fieldQuery->where('name', 'like', '%' . $escapedWord . '%');
                        }
                    });
                });
                
                // ИЛИ поиск по имени упражнения через planExercise.exercise
                $q->orWhereHas('planExercise.exercise', function ($relationQuery) use ($words): void {
                    $relationQuery->where(function ($fieldQuery) use ($words): void {
                        foreach ($words as $word) {
                            $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                            $fieldQuery->where('name', 'like', '%' . $escapedWord . '%');
                        }
                    });
                });
                
                // ИЛИ поиск по имени пользователя через workout.user
                $q->orWhereHas('workout.user', function ($relationQuery) use ($words): void {
                    $relationQuery->where(function ($fieldQuery) use ($words): void {
                        foreach ($words as $word) {
                            $escapedWord = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $word);
                            $fieldQuery->where('name', 'like', '%' . $escapedWord . '%');
                        }
                    });
                });
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
