<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Metric;
use App\Filters\MetricFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class MetricService
{
    use HasPagination;
    
    /**
     * Get all metrics with optional filtering and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new MetricFilter($filters);
        $query = Metric::query()->with(['user']);
        
        // Если user_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['user_id']) || $filters['user_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Get metric by ID.
     */
    public function getById(int $id, ?int $userId = null): Metric
    {
        $query = Metric::query()->with(['user']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->findOrFail($id);
    }

    /**
     * Create a new metric.
     */
    public function create(array $data): Metric
    {
        return Metric::create($data);
    }

    /**
     * Update metric by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): Metric
    {
        $query = Metric::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $metric = $query->findOrFail($id);
        $metric->update($data);
        
        return $metric->fresh(['user']);
    }

    /**
     * Delete metric by ID.
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Metric::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $metric = $query->findOrFail($id);
        
        return $metric->delete();
    }
}
