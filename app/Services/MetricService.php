<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Metric;
use App\Filters\MetricFilter;
use App\Services\GoalService;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class MetricService
{
    use HasPagination;

    public function __construct(
        private readonly GoalService $goalService
    ) {}
    
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
    public function getById(int $id, ?int $userId = null): ?Metric
    {
        $query = Metric::query()->with(['user']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->find($id);
    }

    /**
     * Create a new metric.
     */
    public function create(array $data): Metric
    {
        $metric = Metric::create($data);
        
        // Обновляем прогресс целей пользователя
        if (isset($data['user_id'])) {
            $this->goalService->updateProgressForUser($data['user_id']);
        }
        
        return $metric;
    }

    /**
     * Update metric by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): ?Metric
    {
        $query = Metric::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $metric = $query->find($id);
        
        if (!$metric) {
            return null;
        }
        
        $metric->update($data);
        
        $metric = $metric->fresh(['user']);
        
        // Обновляем прогресс целей пользователя
        if ($metric->user_id) {
            $this->goalService->updateProgressForUser($metric->user_id);
        }
        
        return $metric;
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

        $metric = $query->find($id);
        
        if (!$metric) {
            return false;
        }
        
        return $metric->delete();
    }
}
