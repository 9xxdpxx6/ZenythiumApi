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
     * @param string|null $filters['search'] Название плана (поиск по частичному совпадению)
     * @param bool|null $filters['is_active'] Статус активности плана
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
            $query->whereHas('cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
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
     * 
     * @return Plan Созданная модель плана
     * 
     * @throws \Illuminate\Database\QueryException При ошибке создания записи
     */
    public function create(array $data): Plan
    {
        return Plan::create($data);
    }

    /**
     * Обновить план тренировок по ID
     * 
     * @param int $id ID плана
     * @param array $data Данные для обновления
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
            $query->whereHas('cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $plan = $query->find($id);
        if (!$plan) {
            return null;
        }
        
        $plan->update($data);
        
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
            $query->whereHas('cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $plan = $query->find($id);
        if (!$plan) {
            return false;
        }
        
        return $plan->delete();
    }
}
