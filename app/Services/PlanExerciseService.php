<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PlanExercise;
use App\Models\Plan;
use App\Models\Exercise;
use Illuminate\Database\Eloquent\Collection;

final class PlanExerciseService
{
    /**
     * Получить все упражнения плана
     * 
     * @param int $planId ID плана
     * @param int|null $userId ID пользователя для проверки доступа
     * 
     * @return Collection|null Коллекция упражнений плана или null если план не найден
     */
    public function getByPlanId(int $planId, ?int $userId = null): ?Collection
    {
        $query = PlanExercise::query()
            ->with(['exercise.muscleGroup'])
            ->where('plan_id', $planId)
            ->orderBy('order');

        if ($userId) {
            $query->whereHas('plan.cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        $planExercises = $query->get();
        
        // Проверяем, что план существует и принадлежит пользователю
        if ($planExercises->isEmpty() && $userId) {
            $planExists = Plan::query()
                ->whereHas('cycle', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->where('id', $planId)
                ->exists();
                
            if (!$planExists) {
                return null;
            }
        }

        return $planExercises;
    }

    /**
     * Создать новое упражнение в плане
     * 
     * @param array $data Данные для создания
     * @param int $data['plan_id'] ID плана
     * @param int $data['exercise_id'] ID упражнения
     * @param int|null $data['order'] Порядок упражнения
     * @param int|null $userId ID пользователя для проверки доступа
     * 
     * @return PlanExercise|null Созданная модель или null если план/упражнение не найдены
     */
    public function create(array $data, ?int $userId = null): ?PlanExercise
    {
        // Проверяем существование плана и принадлежность пользователю
        $planQuery = Plan::query()->where('id', $data['plan_id']);
        if ($userId) {
            $planQuery->whereHas('cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }
        
        if (!$planQuery->exists()) {
            return null;
        }

        // Проверяем существование упражнения и принадлежность пользователю
        $exerciseQuery = Exercise::query()->where('id', $data['exercise_id']);
        if ($userId) {
            $exerciseQuery->where('user_id', $userId);
        }
        
        if (!$exerciseQuery->exists()) {
            return null;
        }

        // Проверяем, что упражнение еще не добавлено в план
        if (PlanExercise::where('plan_id', $data['plan_id'])
            ->where('exercise_id', $data['exercise_id'])
            ->exists()) {
            return null;
        }

        // Если порядок не указан, устанавливаем максимальный + 1
        if (!isset($data['order'])) {
            $maxOrder = PlanExercise::where('plan_id', $data['plan_id'])->max('order') ?? 0;
            $data['order'] = $maxOrder + 1;
        }

        return PlanExercise::create($data);
    }

    /**
     * Обновить упражнение в плане
     * 
     * @param int $planExerciseId ID упражнения в плане
     * @param array $data Данные для обновления
     * @param int|null $userId ID пользователя для проверки доступа
     * @param int|null $planId ID плана для дополнительной проверки
     * 
     * @return PlanExercise|null Обновленная модель или null если не найдена
     */
    public function update(int $planExerciseId, array $data, ?int $userId = null, ?int $planId = null): ?PlanExercise
    {
        $query = PlanExercise::query()->where('id', $planExerciseId);

        if ($userId) {
            $query->whereHas('plan.cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        if ($planId) {
            $query->where('plan_id', $planId);
        }

        $planExercise = $query->first();
        
        if (!$planExercise) {
            return null;
        }

        $planExercise->update($data);
        
        return $planExercise->fresh(['exercise.muscleGroup']);
    }

    /**
     * Удалить упражнение из плана
     * 
     * @param int $planExerciseId ID упражнения в плане
     * @param int|null $userId ID пользователя для проверки доступа
     * @param int|null $planId ID плана для дополнительной проверки
     * 
     * @return bool True если успешно удалено, false если не найдено
     */
    public function delete(int $planExerciseId, ?int $userId = null, ?int $planId = null): bool
    {
        $query = PlanExercise::query()->where('id', $planExerciseId);

        if ($userId) {
            $query->whereHas('plan.cycle', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }

        if ($planId) {
            $query->where('plan_id', $planId);
        }

        $planExercise = $query->first();
        
        if (!$planExercise) {
            return false;
        }

        return $planExercise->delete();
    }
}
