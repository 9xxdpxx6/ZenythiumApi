<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Workout;
use App\Filters\WorkoutFilter;
use App\Traits\HasPagination;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class WorkoutService
{
    use HasPagination;
    
    /**
     * Получить все тренировки с фильтрацией и пагинацией
     * 
     * @param array $filters Массив фильтров для поиска тренировок
     * @param int|null $filters['user_id'] ID пользователя (обязательно для безопасности)
     * @param int|null $filters['plan_id'] ID плана тренировки
     * @param string|null $filters['started_at'] Дата начала тренировки (от)
     * @param string|null $filters['finished_at'] Дата окончания тренировки (до)
     * @param int $filters['page'] Номер страницы (по умолчанию 1)
     * @param int $filters['per_page'] Количество элементов на странице (по умолчанию 15)
     * 
     * @return LengthAwarePaginator Пагинированный список тренировок
     * 
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если тренировка не найдена
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $filter = new WorkoutFilter($filters);
        $query = Workout::query()->with(['plan.cycle', 'user']);
        
        // Если user_id не передан, возвращаем пустой результат для безопасности
        if (!isset($filters['user_id']) || $filters['user_id'] === null) {
            return new LengthAwarePaginator([], 0, 15, 1);
        }
        
        $filter->apply($query);

        return $this->applyPagination($query, $filters);
    }

    /**
     * Получить тренировку по ID
     * 
     * @param int $id ID тренировки
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return Workout|null Модель тренировки с загруженными связями или null если не найдена
     */
    public function getById(int $id, ?int $userId = null): ?Workout
    {
        $query = Workout::query()->with(['plan.cycle', 'user']);

        if ($userId) {
            $query->where('user_id', $userId);
        }

        return $query->find($id);
    }

    /**
     * Создать новую тренировку
     * 
     * @param array $data Данные для создания тренировки
     * @param int $data['plan_id'] ID плана тренировки
     * @param int $data['user_id'] ID пользователя
     * @param string|null $data['started_at'] Время начала тренировки
     * @param string|null $data['finished_at'] Время окончания тренировки
     * 
     * @return Workout Созданная модель тренировки
     * 
     * @throws \Illuminate\Database\QueryException При ошибке создания записи
     */
    public function create(array $data): Workout
    {
        return Workout::create($data);
    }

    /**
     * Обновить тренировку по ID
     * 
     * @param int $id ID тренировки
     * @param array $data Данные для обновления
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return Workout|null Обновленная модель тренировки с загруженными связями или null если не найдена
     * 
     * @throws \Illuminate\Database\QueryException При ошибке обновления записи
     */
    public function update(int $id, array $data, ?int $userId = null): ?Workout
    {
        $query = Workout::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $workout = $query->find($id);
        
        if (!$workout) {
            return null;
        }
        
        $workout->update($data);
        
        return $workout->fresh(['plan.cycle', 'user']);
    }

    /**
     * Удалить тренировку по ID
     * 
     * @param int $id ID тренировки
     * @param int|null $userId ID пользователя для проверки доступа (опционально)
     * 
     * @return bool True если тренировка успешно удалена, false если не найдена
     */
    public function delete(int $id, ?int $userId = null): bool
    {
        $query = Workout::query();

        if ($userId) {
            $query->where('user_id', $userId);
        }

        $workout = $query->find($id);
        
        if (!$workout) {
            return false;
        }
        
        return $workout->delete();
    }

    /**
     * Запустить новую тренировку для плана
     * 
     * @param int $planId ID плана тренировки
     * @param int $userId ID пользователя
     * 
     * @return Workout Созданная тренировка с установленным временем начала
     * 
     * @throws \Illuminate\Database\QueryException При ошибке создания записи
     */
    public function start(int $planId, int $userId): Workout
    {
        return Workout::create([
            'plan_id' => $planId,
            'user_id' => $userId,
            'started_at' => now(),
        ]);
    }

    /**
     * Завершить тренировку установкой времени окончания
     * 
     * @param int $workoutId ID тренировки
     * @param int $userId ID пользователя
     * 
     * @return Workout Обновленная тренировка с установленным временем окончания
     * 
     * @throws \InvalidArgumentException Если тренировка не запущена или уже завершена
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException Если тренировка не найдена
     */
    public function finish(int $workoutId, int $userId): ?Workout
    {
        $workout = Workout::where('user_id', $userId)->find($workoutId);
        
        if (!$workout) {
            return null;
        }
        
        // Validate that workout is started but not finished
        if (!$workout->started_at) {
            throw new \InvalidArgumentException('Нельзя завершить незапущенную тренировку');
        }
        
        if ($workout->finished_at) {
            throw new \InvalidArgumentException('Тренировка уже завершена');
        }

        $workout->update(['finished_at' => now()]);
        
        return $workout->fresh(['plan.cycle', 'user']);
    }
}
