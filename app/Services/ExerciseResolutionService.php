<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Exercise;
use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Сервис для разрешения конфликтов при импорте упражнений и названий
 */
final class ExerciseResolutionService
{
    /**
     * Найти существующее упражнение или создать новое
     * 
     * @param array $exerciseData Данные упражнения из программы
     * @param int $userId ID пользователя
     * @param Collection|null $userExercises Предзагруженные упражнения пользователя (для оптимизации)
     * @param Collection|null $muscleGroups Предзагруженные группы мышц (для оптимизации)
     * 
     * @return Exercise Упражнение (существующее или созданное)
     */
    public function resolveOrCreateExercise(
        array $exerciseData,
        int $userId,
        ?Collection $userExercises = null,
        ?Collection $muscleGroups = null
    ): Exercise {
        $name = $exerciseData['name'];
        // Поддержка обоих форматов: muscle_group_id (старый) и muscle_group (новый)
        if (isset($exerciseData['muscle_group_id'])) {
            $muscleGroupId = $exerciseData['muscle_group_id'];
        } elseif (isset($exerciseData['muscle_group']) && $exerciseData['muscle_group'] !== null) {
            $muscleGroupId = $exerciseData['muscle_group']['id'] ?? null;
        } else {
            $muscleGroupId = null;
        }
        $description = $exerciseData['description'] ?? null;

        // Если упражнения предзагружены, ищем в памяти
        if ($userExercises !== null) {
            $existingExercise = $userExercises->first(function ($exercise) use ($name, $muscleGroupId) {
                return $exercise->name === $name && $exercise->muscle_group_id == $muscleGroupId;
            });

            if ($existingExercise) {
                return $existingExercise;
            }

            // Если упражнение с таким названием есть, но другая группа мышц
            $existingWithDifferentGroup = $userExercises->first(function ($exercise) use ($name, $muscleGroupId) {
                return $exercise->name === $name && $exercise->muscle_group_id != $muscleGroupId;
            });

            if ($existingWithDifferentGroup && $muscleGroupId) {
                // Получаем название группы мышц из предзагруженных или из БД
                $muscleGroup = null;
                if ($muscleGroups !== null) {
                    $muscleGroup = $muscleGroups->firstWhere('id', $muscleGroupId);
                } else {
                    $muscleGroup = MuscleGroup::find($muscleGroupId);
                }
                $groupName = $muscleGroup ? $muscleGroup->name : '';

                // Добавляем название группы к названию упражнения
                $name = $name . ' (' . $groupName . ')';
            }
        } else {
            // Ищем существующее упражнение по name + muscle_group_id + user_id
            $existingExercise = Exercise::where('user_id', $userId)
                ->where('name', $name)
                ->where('muscle_group_id', $muscleGroupId)
                ->first();

            if ($existingExercise) {
                // Используем существующее упражнение
                return $existingExercise;
            }

            // Если упражнение с таким названием есть, но другая группа мышц
            $existingWithDifferentGroup = Exercise::where('user_id', $userId)
                ->where('name', $name)
                ->where('muscle_group_id', '!=', $muscleGroupId)
                ->first();

            if ($existingWithDifferentGroup && $muscleGroupId) {
                // Получаем название группы мышц
                $muscleGroup = MuscleGroup::find($muscleGroupId);
                $groupName = $muscleGroup ? $muscleGroup->name : '';

                // Добавляем название группы к названию упражнения
                $name = $name . ' (' . $groupName . ')';
            }
        }

        // Создаем новое упражнение
        return Exercise::create([
            'user_id' => $userId,
            'name' => $name,
            'description' => $description,
            'muscle_group_id' => $muscleGroupId,
            'is_active' => true,
        ]);
    }

    /**
     * Разрешить уникальное название для сущности
     * 
     * @param string $modelClass Класс модели
     * @param string $baseName Базовое название
     * @param int $userId ID пользователя
     * @param SupportCollection|null $existingNames Предзагруженные названия (для оптимизации)
     * 
     * @return string Уникальное название
     */
    public function resolveUniqueName(
        string $modelClass,
        string $baseName,
        int $userId,
        ?SupportCollection $existingNames = null
    ): string {
        $name = $baseName;
        $counter = 1;

        // Если названия предзагружены, проверяем в памяти
        if ($existingNames !== null) {
            while ($existingNames->contains($name)) {
                $name = $baseName . ' ' . $counter;
                $counter++;
            }
        } else {
            // Иначе проверяем в БД
            while ($modelClass::where('user_id', $userId)
                ->where('name', $name)
                ->exists()) {
                $name = $baseName . ' ' . $counter;
                $counter++;
            }
        }

        return $name;
    }
}

