<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\WorkoutSet;
use App\Models\Workout;
use App\Models\PlanExercise;
use App\Filters\WorkoutSetFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class WorkoutSetService
{
    use HasPagination;
    
    /**
     * Get all workout sets with optional filtering and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new WorkoutSetFilter($filters);
        $query = WorkoutSet::query()->with(['workout.plan.cycle', 'workout.user', 'planExercise.exercise']);
        
        // Если workout_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['workout_id']) || $filters['workout_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Get workout set by ID.
     */
    public function getById(int $id, ?int $userId = null): WorkoutSet
    {
        $query = WorkoutSet::query()->with(['workout.plan.cycle', 'workout.user', 'planExercise.exercise']);

        if ($userId) {
            $query->whereHas('workout', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new workout set.
     */
    public function create(array $data): WorkoutSet
    {
        return WorkoutSet::create($data);
    }

    /**
     * Update workout set by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): WorkoutSet
    {
        $query = WorkoutSet::query();

        if ($userId) {
            $query->whereHas('workout', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $workoutSet = $query->findOrFail($id);
        $workoutSet->update($data);
        
        return $workoutSet->fresh(['workout.plan.cycle', 'workout.user', 'planExercise.exercise']);
    }

    /**
     * Delete workout set by ID.
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = WorkoutSet::query();

        if ($userId) {
            $query->whereHas('workout', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $workoutSet = $query->findOrFail($id);
        
        return $workoutSet->delete();
    }

    /**
     * Get workout sets by workout ID.
     */
    public function getByWorkoutId(int $workoutId, ?int $userId = null): Collection
    {
        $query = WorkoutSet::query()
            ->with(['workout.plan.cycle', 'workout.user', 'planExercise.exercise'])
            ->where('workout_id', $workoutId);

        if ($userId) {
            $query->whereHas('workout', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $results = $query->get();
        
        // Если пользователь указан и результатов нет, проверяем существование workout
        if ($userId && $results->isEmpty()) {
            $workout = Workout::find($workoutId);
            if ($workout && $workout->user_id !== $userId) {
                throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
            }
        }

        return $results;
    }

    /**
     * Get workout sets by plan exercise ID.
     */
    public function getByPlanExerciseId(int $planExerciseId, ?int $userId = null): Collection
    {
        $query = WorkoutSet::query()
            ->with(['workout.plan.cycle', 'workout.user', 'planExercise.exercise'])
            ->where('plan_exercise_id', $planExerciseId);

        if ($userId) {
            $query->whereHas('workout', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $results = $query->get();
        
        // Если пользователь указан и результатов нет, проверяем существование plan exercise
        if ($userId && $results->isEmpty()) {
            $planExercise = PlanExercise::find($planExerciseId);
            if ($planExercise) {
                $workout = Workout::whereHas('plan', function ($q) use ($planExercise) {
                    $q->where('id', $planExercise->plan_id);
                })->where('user_id', $userId)->first();
                
                if (!$workout) {
                    throw new \Illuminate\Database\Eloquent\ModelNotFoundException();
                }
            }
        }

        return $results;
    }
}
