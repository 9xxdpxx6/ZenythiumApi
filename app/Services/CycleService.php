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
            
            if ($plan) {
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
        // Получаем текущие планы цикла
        $existingPlans = \App\Models\Plan::where('cycle_id', $cycle->id)->get();
        $existingPlanIds = $existingPlans->pluck('id')->toArray();
        
        // Планы, которые нужно отвязать (есть в цикле, но нет в новом списке)
        $plansToDetach = array_diff($existingPlanIds, $planIds);
        
        // Планы, которые нужно привязать (есть в новом списке, но нет в цикле)
        $plansToAttach = array_diff($planIds, $existingPlanIds);
        
        // Отвязываем планы, которых нет в новом списке
        if (!empty($plansToDetach)) {
            foreach ($plansToDetach as $planId) {
                $plan = \App\Models\Plan::find($planId);
                if ($plan) {
                    $plan->update(['cycle_id' => null]);
                }
            }
        }
        
        // Привязываем новые планы и обновляем порядок всех планов
        foreach ($planIds as $index => $planId) {
            $plan = \App\Models\Plan::find($planId);
            
            if ($plan) {
                $plan->update([
                    'cycle_id' => $cycle->id,
                    'order' => $index + 1
                ]);
            }
        }
    }
}
