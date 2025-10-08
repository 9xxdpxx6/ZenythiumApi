<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Plan;
use App\Filters\PlanFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class PlanService
{
    use HasPagination;
    
    /**
     * Get all plans with optional filtering and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new PlanFilter($filters);
        $query = Plan::query()->with('cycle');
        
        // Если user_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['user_id']) || $filters['user_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Get plan by ID.
     */
    public function getById(int $id, ?int $userId = null): Plan
    {
        $query = Plan::query()->with('cycle');

        if ($userId) {
            $query->whereHas('cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new plan.
     */
    public function create(array $data): Plan
    {
        return Plan::create($data);
    }

    /**
     * Update plan by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): Plan
    {
        $query = Plan::query();

        if ($userId) {
            $query->whereHas('cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $plan = $query->findOrFail($id);
        $plan->update($data);
        
        return $plan->fresh(['cycle']);
    }

    /**
     * Delete plan by ID.
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Plan::query();

        if ($userId) {
            $query->whereHas('cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $plan = $query->findOrFail($id);
        
        return $plan->delete();
    }
}
