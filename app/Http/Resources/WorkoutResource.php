<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API ресурс для тренировок
 * 
 * Преобразует модель тренировки в массив для JSON ответа API.
 * Включает основную информацию о тренировке, связанные данные плана и пользователя,
 * а также список упражнений из плана с историей их выполнения за последние 3 тренировки.
 * 
 * @property int $id ID тренировки
 * @property string|null $started_at Время начала тренировки в ISO 8601 формате
 * @property string|null $finished_at Время окончания тренировки в ISO 8601 формате
 * @property int|null $duration_minutes Продолжительность тренировки в минутах
 * @property int $exercise_count Количество упражнений в тренировке
 * @property float $total_volume Общий объем тренировки (вес × повторения)
 * @property object $plan Данные плана тренировки (id, name)
 * @property object $user Данные пользователя (id, name)
 * @property array $exercises Список упражнений из плана с историей выполнения
 * @property string|null $created_at Время создания записи в ISO 8601 формате
 * @property string|null $updated_at Время последнего обновления в ISO 8601 формате
 */
final class WorkoutResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для JSON ответа
     * 
     * @param Request $request HTTP запрос
     * 
     * @return array Массив с данными тренировки:
     * - id: ID тренировки
     * - started_at: время начала в ISO 8601 формате
     * - finished_at: время окончания в ISO 8601 формате
     * - duration_minutes: продолжительность в минутах (вычисляемый атрибут)
     * - exercise_count: количество упражнений (вычисляемый атрибут)
     * - total_volume: общий объем тренировки (вычисляемый атрибут)
     * - plan: объект с данными плана тренировки (id, name)
     * - user: объект с данными пользователя (id, name)
     * - exercises: массив упражнений из плана с историей выполнения за последние 3 тренировки
     *   - id: ID упражнения в плане
     *   - order: порядок выполнения упражнения
     *   - exercise: объект с информацией об упражнении (id, name, description, muscle_group)
     *   - history: массив истории выполнения за последние 3 тренировки
     *     - workout_id: ID тренировки
     *     - workout_date: дата завершения тренировки
     *     - sets: массив подходов (id, weight, reps)
     * - created_at: время создания записи в ISO 8601 формате
     * - updated_at: время последнего обновления в ISO 8601 формате
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),
            'duration_minutes' => $this->duration_minutes,
            'exercise_count' => $this->exercise_count,
            'total_volume' => $this->total_volume,
            'plan' => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
            ],
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'exercises' => $this->when($this->relationLoaded('plan') && $this->plan->relationLoaded('planExercises'), function () {
                return $this->plan->planExercises->map(function ($planExercise) {
                    return [
                        'id' => $planExercise->id,
                        'order' => $planExercise->order,
                        'exercise' => [
                            'id' => $planExercise->exercise->id,
                            'name' => $planExercise->exercise->name,
                            'description' => $planExercise->exercise->description,
                            'muscle_group' => [
                                'id' => $planExercise->exercise->muscleGroup->id,
                                'name' => $planExercise->exercise->muscleGroup->name,
                            ],
                        ],
                        // История должна быть привязана к упражнению, а не к конкретному плану/циклу
                        'history' => $this->getExerciseHistory($planExercise->exercise->id),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString() ?? null,
            'updated_at' => $this->updated_at?->toISOString() ?? null,
        ];
    }

    /**
     * Получить историю выполнения упражнения за последние тренировки
     * 
     * Возвращает историю подходов для упражнения, включая:
     * - Текущую тренировку (если есть подходы)
     * - Последние 3 завершенные тренировки (finished_at != null)
     * 
     * Использует предзагруженные данные из контроллера для оптимизации производительности.
     * 
     * @param int $exerciseId ID упражнения
     * 
     * @return array Массив с историей подходов. Каждый элемент содержит:
     * - workout_id: ID тренировки
     * - workout_date: дата завершения тренировки в ISO 8601 формате 
     *   (null для незавершенных тренировок, показывает статус завершения)
     * - sets: массив подходов с id, weight, reps
     * 
     * @note workout_date = null означает незавершенную тренировку (finished_at = null)
     * @note workout_date = дата означает завершенную тренировку (finished_at != null)
     * @note started_at не используется в истории, только finished_at для определения статуса
     */
    private function getExerciseHistory(int $exerciseId): array
    {
        // Используем предзагруженные данные из контроллера (оптимизация N+1)
        $allWorkoutSets = $this->resource->getAttribute('exerciseHistorySets');
        
        if (!$allWorkoutSets || !$allWorkoutSets->has($exerciseId)) {
            return [];
        }
        
        // Получаем подходы для конкретного упражнения и группируем по тренировкам
        $workoutSets = $allWorkoutSets->get($exerciseId)->groupBy('workout_id');
        
        $history = [];
        
        // Добавляем текущую тренировку (если есть подходы по этому упражнению)
        if ($workoutSets->has($this->id)) {
            $currentSets = $workoutSets->get($this->id);
            $history[] = [
                'workout_id' => $this->id,
                'workout_date' => $this->finished_at?->toISOString(),
                'sets' => $currentSets->map(function ($set) {
                    return [
                        'id' => $set->id,
                        'weight' => $set->weight,
                        'reps' => $set->reps,
                    ];
                })->values()->toArray(),
            ];
        }
        
        // Добавляем последние 3 завершенные тренировки (исключая текущую)
        $completedWorkouts = $workoutSets
            ->filter(function ($sets, $workoutId) {
                return $workoutId != $this->id && $sets->first()->workout->finished_at !== null;
            })
            ->sortByDesc(function ($sets) {
                return $sets->first()->workout->finished_at;
            })
            ->take(3);

        foreach ($completedWorkouts as $workoutId => $sets) {
            $workout = $sets->first()->workout;
            $history[] = [
                'workout_id' => $workoutId,
                'workout_date' => $workout->finished_at?->toISOString(),
                'sets' => $sets->map(function ($set) {
                    return [
                        'id' => $set->id,
                        'weight' => $set->weight,
                        'reps' => $set->reps,
                    ];
                })->values()->toArray(),
            ];
        }

        return $history;
    }
}
