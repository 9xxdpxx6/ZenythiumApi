<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cycle;
use App\Filters\CycleFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class CycleService
{
    use HasPagination;
    
    /**
     * Get all cycles with optional filtering and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new CycleFilter($filters);
        $query = Cycle::query();
        
        // Если user_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['user_id']) || $filters['user_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Get cycle by ID.
     */
    public function getById(int $id, ?int $userId = null): Cycle
    {
        $query = Cycle::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new cycle.
     */
    public function create(array $data): Cycle
    {
        return Cycle::create($data);
    }

    /**
     * Update cycle by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): Cycle
    {
        $query = Cycle::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $cycle = $query->findOrFail($id);
        $cycle->update($data);
        
        return $cycle->fresh();
    }

    /**
     * Delete cycle by ID.
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Cycle::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $cycle = $query->findOrFail($id);
        
        return $cycle->delete();
    }
}
