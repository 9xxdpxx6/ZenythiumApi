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
     * @return Workout|null Модель тренировки с загруженными связями или null если не найдена.
     * Загружает связи: plan.cycle, user, plan.planExercises.exercise.muscleGroup
     */
    public function getById(int $id, ?int $userId = null): ?Workout
    {
        $query = Workout::query()->with([
            'plan.cycle', 
            'user',
            'plan.planExercises.exercise.muscleGroup'
        ]);

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
     * 
     * @note При удалении тренировки автоматически удаляются все связанные WorkoutSet записи
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
     * Автоматически определить план для следующей тренировки на основе активного цикла
     * 
     * @param int $userId ID пользователя
     * 
     * @return int|null ID плана для следующей тренировки или null если не найден активный цикл
     */
    public function determineNextPlan(int $userId): ?int
    {
        // Находим активный цикл пользователя (последний по дате создания)
        // Активным считается цикл, у которого есть активные планы
        $activeCycle = \App\Models\Cycle::where('user_id', $userId)
            ->whereHas('plans', function ($query) {
                $query->where('is_active', true);
            })
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$activeCycle) {
            return null;
        }

        // Получаем только активные планы этого цикла в порядке их выполнения
        $plans = $activeCycle->plans()
            ->where('is_active', true)
            ->orderBy('order')
            ->get();

        if ($plans->isEmpty()) {
            return null;
        }

        // Подсчитываем завершенные тренировки для каждого плана
        $planProgress = [];
        foreach ($plans as $plan) {
            $completedWorkouts = $plan->workouts()
                ->where('user_id', $userId)
                ->whereNotNull('finished_at')
                ->count();
            
            $planProgress[$plan->id] = [
                'plan' => $plan,
                'completed_workouts' => $completedWorkouts,
                'order' => $plan->order
            ];
        }

        // Определяем следующий план на основе логики:
        // Выбираем план с наименьшим количеством завершенных тренировок
        // Если несколько планов имеют одинаковое минимальное количество, берем первый по порядку
        
        $minCompletedWorkouts = min(array_column($planProgress, 'completed_workouts'));
        
        // Находим план с минимальным количеством завершенных тренировок
        // Если несколько планов имеют одинаковое минимальное количество, берем первый по порядку
        foreach ($plans as $plan) {
            if ($planProgress[$plan->id]['completed_workouts'] === $minCompletedWorkouts) {
                return $plan->id;
            }
        }

        return null;
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

    /**
     * Получить историю выполнения упражнения за последние 3 тренировки
     * 
     * Возвращает подходы для указанного упражнения из плана за последние 3 завершенные тренировки.
     * Подходы отсортированы по дате создания (новые сначала) и сгруппированы по тренировкам.
     * 
     * @param int $planExerciseId ID упражнения в плане
     * @param int $userId ID пользователя
     * 
     * @return \Illuminate\Database\Eloquent\Collection Коллекция подходов за последние 3 тренировки.
     * Каждый подход содержит связь с тренировкой (id, started_at, finished_at).
     */
    public function getExerciseHistory(int $planExerciseId, int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\WorkoutSet::where('plan_exercise_id', $planExerciseId)
            ->whereHas('workout', function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->whereNotNull('finished_at');
            })
            ->with(['workout' => function ($query) {
                $query->select('id', 'started_at', 'finished_at');
            }])
            ->orderBy('created_at', 'desc')
            ->limit(3)
            ->get();
    }
}
