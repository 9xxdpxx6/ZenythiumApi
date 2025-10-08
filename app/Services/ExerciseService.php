<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exercise;
use App\Filters\ExerciseFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class ExerciseService
{
    /**
     * Get all exercises with optional filtering and pagination.
     */
    public function getAll(array $filters = []): Collection|LengthAwarePaginator
    {
        $filter = new ExerciseFilter($filters);
        $query = Exercise::query()->with('muscleGroup');
        
        $filter->apply($query);

        // Apply pagination if requested
        if (isset($filters['per_page'])) {
            $paginationParams = $filter->getPaginationParams();
            return $query->paginate($paginationParams['per_page']);
        }

        return $query->get();
    }

    /**
     * Get exercise by ID.
     */
    public function getById(int $id, ?int $userId = null): Exercise
    {
        $query = Exercise::query()->with('muscleGroup');

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new exercise.
     */
    public function create(array $data): Exercise
    {
        return Exercise::create($data);
    }

    /**
     * Update exercise by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): Exercise
    {
        $query = Exercise::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $exercise = $query->findOrFail($id);
        $exercise->update($data);
        
        return $exercise->fresh(['muscleGroup']);
    }

    /**
     * Delete exercise by ID.
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Exercise::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $exercise = $query->findOrFail($id);
        
        return $exercise->delete();
    }
}
