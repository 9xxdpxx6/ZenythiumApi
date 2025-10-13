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
        $query = Cycle::query()->withCount('plans');
        
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
    public function getById(int $id, ?int $userId = null): ?Cycle
    {
        $query = Cycle::query()->with(['plans' => function ($query) {
            $query->orderBy('order');
        }]);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->find($id);
    }

    /**
     * Create a new cycle.
     */
    public function create(array $data): Cycle
    {
        $planIds = $data['plan_ids'] ?? [];
        unset($data['plan_ids']);
        
        $cycle = Cycle::create($data);
        
        // Привязываем планы к циклу, если они указаны
        if (!empty($planIds)) {
            $this->attachPlansToCycle($cycle, $planIds);
        }
        
        return $cycle;
    }

    /**
     * Update cycle by ID.
     */
    public function update(int $id, array $data, ?int $userId = null): ?Cycle
    {
        $query = Cycle::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $cycle = $query->find($id);
        
        if (!$cycle) {
            return null;
        }
        
        $planIds = $data['plan_ids'] ?? null;
        unset($data['plan_ids']);
        
        $cycle->update($data);
        
        // Обновляем привязку планов, если они указаны
        if ($planIds !== null) {
            $this->syncPlansToCycle($cycle, $planIds);
        }
        
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

        $cycle = $query->find($id);
        
        if (!$cycle) {
            return false;
        }
        
        return $cycle->delete();
    }
    
    /**
     * Attach plans to cycle (for create operation).
     */
    private function attachPlansToCycle(Cycle $cycle, array $planIds): void
    {
        foreach ($planIds as $index => $planId) {
            $plan = \App\Models\Plan::find($planId);
            
            // Проверяем, что план принадлежит тому же пользователю
            if ($plan && $plan->cycle && $plan->cycle->user_id === $cycle->user_id) {
                $plan->update([
                    'cycle_id' => $cycle->id,
                    'order' => $index + 1
                ]);
            }
        }
    }
    
    /**
     * Sync plans to cycle (for update operation).
     */
    private function syncPlansToCycle(Cycle $cycle, array $planIds): void
    {
        // Сначала отвязываем все планы от этого цикла, перемещая их в другой цикл
        $existingPlans = \App\Models\Plan::where('cycle_id', $cycle->id)->get();
        
        if ($existingPlans->isNotEmpty()) {
            // Находим другой цикл пользователя или создаем новый
            $otherCycle = \App\Models\Cycle::where('user_id', $cycle->user_id)
                ->where('id', '!=', $cycle->id)
                ->first();
                
            if (!$otherCycle) {
                $otherCycle = \App\Models\Cycle::create([
                    'name' => 'Unassigned Plans',
                    'weeks' => 1,
                    'user_id' => $cycle->user_id
                ]);
            }
            
            foreach ($existingPlans as $plan) {
                $plan->update(['cycle_id' => $otherCycle->id]);
            }
        }
        
        // Затем привязываем новые планы в порядке массива
        foreach ($planIds as $index => $planId) {
            $plan = \App\Models\Plan::find($planId);
            
            // Проверяем, что план принадлежит тому же пользователю
            if ($plan && $plan->cycle && $plan->cycle->user_id === $cycle->user_id) {
                $plan->update([
                    'cycle_id' => $cycle->id,
                    'order' => $index + 1
                ]);
            }
        }
    }
}
