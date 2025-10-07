<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class MuscleGroupService
{
    /**
     * Get all muscle groups with optional filtering and pagination.
     */
    public function getAll(array $filters = []): Collection|LengthAwarePaginator
    {
        $query = MuscleGroup::query();

        // Apply filters if provided
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%' . $filters['search'] . '%');
        }

        // Load exercises count for specific user if user_id is provided
        if (isset($filters['user_id'])) {
            $query->withCount(['exercises' => function ($query) use ($filters) {
                $query->where('user_id', $filters['user_id']);
            }]);
        }

        // Apply pagination if requested
        if (isset($filters['per_page'])) {
            return $query->paginate((int) $filters['per_page']);
        }

        return $query->get();
    }

    /**
     * Get muscle group by ID.
     */
    public function getById(int $id, ?int $userId = null): MuscleGroup
    {
        $query = MuscleGroup::query();

        // Load exercises count for specific user if user_id is provided
        if ($userId) {
            $query->withCount(['exercises' => function ($query) use ($userId) {
                $query->where('user_id', $userId);
            }]);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new muscle group.
     */
    public function create(array $data): MuscleGroup
    {
        return MuscleGroup::create($data);
    }

    /**
     * Update muscle group by ID.
     */
    public function update(int $id, array $data): MuscleGroup
    {
        $muscleGroup = MuscleGroup::findOrFail($id);
        $muscleGroup->update($data);
        
        return $muscleGroup->fresh();
    }

    /**
     * Delete muscle group by ID.
     */
    public function delete(int $id): bool
    {
        $muscleGroup = MuscleGroup::findOrFail($id);
        
        return $muscleGroup->delete();
    }
}
