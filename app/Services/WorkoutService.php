<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Workout;
use App\Filters\WorkoutFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class WorkoutService
{
    use HasPagination;
    
    /**
     * Get all workouts with optional filtering and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new WorkoutFilter($filters);
        $query = Workout::query()->with(['plan.cycle', 'user']);
        
        // Если user_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['user_id']) || $filters['user_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Get workout by ID.
     */
    public function getById(int $id, ?int $userId = null): Workout
    {
        $query = Workout::query()->with(['plan.cycle', 'user']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new workout.
     */
    public function create(array $data): Workout
    {
        return Workout::create($data);
    }

    /**
     * Update workout by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): Workout
    {
        $query = Workout::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $workout = $query->findOrFail($id);
        $workout->update($data);
        
        return $workout->fresh(['plan.cycle', 'user']);
    }

    /**
     * Delete workout by ID.
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Workout::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $workout = $query->findOrFail($id);
        
        return $workout->delete();
    }
}
