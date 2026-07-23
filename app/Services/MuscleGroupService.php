<?php

declare(strict_types=1);

namespace App\Services;

use App\Filters\MuscleGroupFilter;
use App\Models\MuscleGroup;
use App\Traits\HasPagination;
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
    public function getById(int $id, ?int $userId = null): ?MuscleGroup
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

        return $query->find($id);
    }
}
