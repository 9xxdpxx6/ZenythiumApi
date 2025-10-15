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
     * Получить все планы тренировок с фильтрацией и пагинацией
     * 
     * @param array $filters Массив фильтров для поиска планов
     * @param int|null $filters['user_id'] ID пользователя (обязательно для безопасности)
     * @param int|null $filters['cycle_id'] ID цикла тренировок
     * @param bool|string|null $filters['standalone'] Фильтр по типу планов:
     *   - true/'true'/'1': только standalone планы (без цикла)
     *   - false/'false'/'0': только планы с циклом, принадлежащие пользователю
     *   - null: все планы пользователя (включая standalone)
     * @param string|null $filters['search'] Название плана (поиск по частичному совпадению)
     * @param int|null $filters['order'] Порядок плана
     * @param bool|null $filters['is_active'] Статус активности плана
     * @param string|null $filters['date_from'] Дата создания от (Y-m-d)
     * @param string|null $filters['date_to'] Дата создания до (Y-m-d)
     * @param string|null $filters['sort_by'] Поле для сортировки (по умолчанию 'created_at')
     * @param string|null $filters['sort_direction'] Направление сортировки (asc/desc, по умолчанию 'desc')
     * @param int $filters['page'] Номер страницы (по умолчанию 1)
     * @param int $filters['per_page'] Количество элементов на странице (по умолчанию 15)
     * 
     * @return LengthAwarePaginator Пагинированный список планов
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если план не найден
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new PlanFilter($filters);
        $query = Plan::query()->with(['cycle', 'planExercises.exercise']);
        
        // Если user_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['user_id']) || $filters['user_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Получить план тренировок по ID
     * 
     * @param int $id ID плана
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return Plan|null Модель плана с загруженной связью цикла или null если не найден
     */
    public function getById(int $id, ?int $userId = null): ?Plan
    {
        $query = Plan::query()->with(['cycle', 'planExercises.exercise.muscleGroup']);

        if ($userId) {
            // План может принадлежать пользователю через цикл или быть без цикла (общие планы)
            $query->where(function ($q) use ($userId) {
                $q->whereHas('cycle', function ($cycleQuery) use ($userId) {
                    $cycleQuery->where('user_id', $userId);
                })->orWhereNull('cycle_id');
            });
        }

        return $query->find($id);
    }

    /**
     * Создать новый план тренировок
     * 
     * @param array $data Данные для создания плана
     * @param string $data['name'] Название плана
     * @param int $data['cycle_id'] ID цикла тренировок
     * @param int|null $data['order'] Порядок плана
     * @param bool|null $data['is_active'] Статус активности плана
     * @param array|null $data['exercise_ids'] Массив ID упражнений для добавления в план
     * 
     * @return Plan Созданная модель плана
     * 
     * @throws \Illuminate\Database\QueryException При ошибке создания записи
     */
    public function create(array $data): Plan
    {
        $exerciseIds = $data['exercise_ids'] ?? [];
        unset($data['exercise_ids']);
        
        $plan = Plan::create($data);
        
        // Добавляем упражнения в план, если они указаны
        if (!empty($exerciseIds)) {
            $this->attachExercisesToPlan($plan, $exerciseIds);
        }
        
        return $plan;
    }

    /**
     * Обновить план тренировок по ID
     * 
     * @param int $id ID плана
     * @param array $data Данные для обновления
     * @param string|null $data['name'] Название плана
     * @param int|null $data['cycle_id'] ID цикла тренировок
     * @param int|null $data['order'] Порядок плана
     * @param bool|null $data['is_active'] Статус активности плана
     * @param array|null $data['exercise_ids'] Массив ID упражнений для синхронизации с планом
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return Plan|null Обновленная модель плана с загруженной связью цикла или null если не найден
     * 
     * @throws \Illuminate\Database\QueryException При ошибке обновления записи
     */
    public function update(int $id, array $data, ?int $userId = null): ?Plan
    {
        $query = Plan::query();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereHas('cycle', function ($cycleQuery) use ($userId) {
                    $cycleQuery->where('user_id', $userId);
                })->orWhereNull('cycle_id');
            });
        }

        $plan = $query->find($id);
        if (!$plan) {
            return null;
        }
        
        $exerciseIds = $data['exercise_ids'] ?? null;
        unset($data['exercise_ids']);
        
        $plan->update($data);
        
        // Обновляем упражнения плана, если они указаны
        if ($exerciseIds !== null) {
            $this->syncExercisesToPlan($plan, $exerciseIds);
        }
        
        return $plan->fresh(['cycle']);
    }

    /**
     * Удалить план тренировок по ID
     * 
     * @param int $id ID плана
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return bool True если план успешно удален, false если план не найден
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Plan::query();

        if ($userId) {
            $query->where(function ($q) use ($userId) {
                $q->whereHas('cycle', function ($cycleQuery) use ($userId) {
                    $cycleQuery->where('user_id', $userId);
                })->orWhereNull('cycle_id');
            });
        }

        $plan = $query->find($id);
        if (!$plan) {
            return false;
        }
        
        return $plan->delete();
    }

    /**
     * Создать копию плана тренировок
     * 
     * @param int $id ID исходного плана
     * @param int|null $newCycleId ID нового цикла для копии (опционально, по умолчанию null)
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * @param string|null $newName Новое название для копии (опционально)
     * 
     * @return Plan|null Созданная копия плана или null если исходный план не найден
     * 
     * @throws \Illuminate\Database\QueryException При ошибке создания записи
     */
    public function duplicate(int $id, ?int $newCycleId = null, ?int $userId = null, ?string $newName = null, ?bool $isActive = null): ?Plan
    {
        // Получаем исходный план с проверкой доступа
        $originalPlan = $this->getById($id, $userId);
        if (!$originalPlan) {
            return null;
        }

        // Проверяем, что новый цикл принадлежит тому же пользователю (если указан)
        if ($newCycleId && $userId) {
            $newCycle = \App\Models\Cycle::where('id', $newCycleId)
                ->where('user_id', $userId)
                ->first();
            
            if (!$newCycle) {
                return null;
            }
        }

        // Определяем название для копии
        $copyName = $newName ?? $originalPlan->name . ' (копия)';

        // Создаем копию плана
        $newPlan = Plan::create([
            'cycle_id' => $newCycleId,
            'name' => $copyName,
            'order' => $originalPlan->order,
            'is_active' => $isActive ?? $originalPlan->is_active,
        ]);

        // Копируем упражнения плана
        foreach ($originalPlan->planExercises as $planExercise) {
            \App\Models\PlanExercise::create([
                'plan_id' => $newPlan->id,
                'exercise_id' => $planExercise->exercise_id,
                'order' => $planExercise->order,
            ]);
        }

        return $newPlan->fresh(['cycle', 'planExercises.exercise']);
    }
    
    /**
     * Добавить упражнения к плану (для создания).
     * 
     * Для планов с циклом проверяет принадлежность упражнений пользователю.
     * Для standalone планов добавляет упражнения без проверки принадлежности.
     * 
     * @param Plan $plan План тренировок
     * @param array $exerciseIds Массив ID упражнений для добавления
     * @return void
     */
    private function attachExercisesToPlan(Plan $plan, array $exerciseIds): void
    {
        foreach ($exerciseIds as $index => $exerciseId) {
            $exercise = \App\Models\Exercise::find($exerciseId);
            
            // Проверяем, что упражнение существует
            if ($exercise) {
                // Для планов с циклом проверяем принадлежность пользователю
                if ($plan->cycle_id !== null) {
                    if ($exercise->user_id === $plan->cycle->user_id) {
                        \App\Models\PlanExercise::create([
                            'plan_id' => $plan->id,
                            'exercise_id' => $exerciseId,
                            'order' => $index + 1
                        ]);
                    }
                } else {
                    // Для standalone планов добавляем без проверки принадлежности
                    \App\Models\PlanExercise::create([
                        'plan_id' => $plan->id,
                        'exercise_id' => $exerciseId,
                        'order' => $index + 1
                    ]);
                }
            }
        }
    }
    
    /**
     * Синхронизировать упражнения плана (для обновления).
     * 
     * Удаляет все существующие упражнения плана и добавляет новые в указанном порядке.
     * Для планов с циклом проверяет принадлежность упражнений пользователю.
     * Для standalone планов добавляет упражнения без проверки принадлежности.
     * 
     * @param Plan $plan План тренировок
     * @param array $exerciseIds Массив ID упражнений для синхронизации
     * @return void
     */
    private function syncExercisesToPlan(Plan $plan, array $exerciseIds): void
    {
        // Удаляем все существующие упражнения плана
        \App\Models\PlanExercise::where('plan_id', $plan->id)->delete();
        
        // Добавляем новые упражнения в порядке массива
        foreach ($exerciseIds as $index => $exerciseId) {
            $exercise = \App\Models\Exercise::find($exerciseId);
            
            // Проверяем, что упражнение существует
            if ($exercise) {
                // Для планов с циклом проверяем принадлежность пользователю
                if ($plan->cycle_id !== null) {
                    if ($exercise->user_id === $plan->cycle->user_id) {
                        \App\Models\PlanExercise::create([
                            'plan_id' => $plan->id,
                            'exercise_id' => $exerciseId,
                            'order' => $index + 1
                        ]);
                    }
                } else {
                    // Для standalone планов добавляем без проверки принадлежности
                    \App\Models\PlanExercise::create([
                        'plan_id' => $plan->id,
                        'exercise_id' => $exerciseId,
                        'order' => $index + 1
                    ]);
                }
            }
        }
    }
}
