<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MuscleGroup;
use App\Filters\MuscleGroupFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class MuscleGroupService
{
    use HasPagination;
    /**
     * Get all muscle groups with optional filtering and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new MuscleGroupFilter($filters);
        $query = MuscleGroup::query();
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
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
        } else {
            $query->withCount(['exercises' => function ($query) {
                $query->where('user_id', -1);
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
