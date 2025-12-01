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
        $query = Cycle::query()
            ->with('user')
            ->withCount([
                'plans',
                'workouts as completed_workouts_count' => function ($query) {
                    $query->whereNotNull('finished_at');
                }
            ]);
        
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
        $query = Cycle::query()->with([
            'user',
            'plans' => function ($query) {
                $query->orderBy('order');
            }
        ])->withCount([
            'plans',
            'workouts as completed_workouts_count' => function ($query) {
                $query->whereNotNull('finished_at');
            }
        ]);

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
        
        // Проверяем и автоматически завершаем цикл при достижении 100%
        $this->autoCompleteIfFinished($cycle);
        
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
        if (empty($planIds)) {
            return;
        }

        // Оптимизация: загружаем все планы одним запросом
        $plans = \App\Models\Plan::whereIn('id', $planIds)->get()->keyBy('id');

        foreach ($planIds as $index => $planId) {
            $plan = $plans->get($planId);
            
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
        
        $plansToDetach = array_diff($existingPlanIds, $planIds);
        
        // Отвязываем планы, которых нет в новом списке (оптимизация: одним запросом)
        if (!empty($plansToDetach)) {
            \App\Models\Plan::whereIn('id', $plansToDetach)->update(['cycle_id' => null]);
        }
        
        if (empty($planIds)) {
            return;
        }

        $plans = \App\Models\Plan::whereIn('id', $planIds)->get()->keyBy('id');
        
        foreach ($planIds as $index => $planId) {
            $plan = $plans->get($planId);
            
            if ($plan) {
                $plan->update([
                    'cycle_id' => $cycle->id,
                    'order' => $index + 1
                ]);
            }
        }
    }

    /**
     * Автоматически завершить цикл при достижении 100% прогресса
     * 
     * Проверяет прогресс цикла и если он достиг 100%, 
     * автоматически устанавливает end_date в текущую дату
     * (только если end_date еще не установлена).
     * 
     * @param Cycle $cycle Цикл для проверки
     * 
     * @return bool True если цикл был завершен, false если нет
     */
    public function autoCompleteIfFinished(Cycle $cycle): bool
    {
        // Если цикл уже завершен (end_date установлена), ничего не делаем
        if ($cycle->end_date !== null) {
            return false;
        }

        // Перезагружаем цикл из БД для получения актуальных данных
        // Используем fresh() с загрузкой связей, чтобы убедиться в актуальности данных
        $freshCycle = Cycle::with('plans')->find($cycle->id);
        
        if (!$freshCycle) {
            return false;
        }
        
        // Проверяем прогресс цикла
        // progress_percentage - это computed attribute, который использует связи plans и workouts
        // Связи workouts загружаются автоматически через hasManyThrough при обращении к методу
        $progress = $freshCycle->progress_percentage;
        
        // Если прогресс достиг 100%, автоматически завершаем цикл
        if ($progress >= 100) {
            $freshCycle->update([
                'end_date' => now()->toDateString()
            ]);
            
            return true;
        }
        
        return false;
    }
}
