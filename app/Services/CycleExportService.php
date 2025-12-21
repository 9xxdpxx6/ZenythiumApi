<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cycle;
use App\Models\Workout;

final class CycleExportService
{
    /**
     * Получить подробные данные цикла для экспорта
     * 
     * @param Cycle $cycle Цикл для экспорта
     * 
     * @return array Массив с подробными данными цикла
     */
    public function getDetailedData(Cycle $cycle): array
    {
        $cycle->load([
            'user',
            'plans.planExercises.exercise.muscleGroup',
        ]);

        $cycle->load([
            'workouts' => function ($query) {
                $query->with([
                    'plan',
                    'workoutSets.planExercise.exercise'
                ])->orderBy('started_at', 'desc');
            }
        ]);

        $plans = $cycle->plans->map(function ($plan) {
            $plan->load('planExercises.exercise.muscleGroup');
            
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'order' => $plan->order,
                'is_active' => $plan->is_active,
                'exercise_count' => $plan->exercise_count,
                'exercises' => $plan->planExercises->map(function ($planExercise) {
                    return [
                        'id' => $planExercise->exercise->id,
                        'name' => $planExercise->exercise->name,
                        'description' => $planExercise->exercise->description,
                        'muscle_group' => $planExercise->exercise->muscleGroup ? [
                            'id' => $planExercise->exercise->muscleGroup->id,
                            'name' => $planExercise->exercise->muscleGroup->name,
                        ] : null,
                        'order' => $planExercise->order,
                    ];
                })->toArray(),
                'created_at' => $plan->created_at?->toISOString(),
                'updated_at' => $plan->updated_at?->toISOString(),
            ];
        })->toArray();

        $workouts = $cycle->workouts->map(function ($workout) {
            // Группируем подходы по упражнениям
            $exercises = [];
            foreach ($workout->workoutSets as $set) {
                $exerciseId = $set->planExercise->exercise_id;
                $exerciseName = $set->planExercise->exercise->name;
                
                if (!isset($exercises[$exerciseId])) {
                    $exercises[$exerciseId] = [
                        'name' => $exerciseName,
                        'sets' => [],
                    ];
                }
                
                $exercises[$exerciseId]['sets'][] = [
                    'weight' => $set->weight,
                    'reps' => $set->reps,
                ];
            }
            
            // Преобразуем в массив и сортируем по порядку
            $exercisesArray = array_values($exercises);
            
            return [
                'id' => $workout->id,
                'plan_id' => $workout->plan_id,
                'plan_name' => $workout->plan?->name,
                'started_at' => $workout->started_at?->toISOString(),
                'finished_at' => $workout->finished_at?->toISOString(),
                'duration_minutes' => $workout->finished_at && $workout->started_at
                    ? (int) round($workout->started_at->diffInMinutes($workout->finished_at))
                    : null,
                'exercises' => $exercisesArray,
            ];
        })->toArray();

        $plansCount = $cycle->plans->count();
        $totalScheduledWorkouts = $cycle->weeks * $plansCount;
        $completedWorkouts = $cycle->workouts->whereNotNull('finished_at')->count();

        return [
            'id' => $cycle->id,
            'name' => $cycle->name,
            'user' => [
                'id' => $cycle->user->id,
                'name' => $cycle->user->name,
            ],
            'start_date' => $cycle->start_date?->toDateString(),
            'end_date' => $cycle->end_date?->toDateString(),
            'weeks' => $cycle->weeks,
            'progress_percentage' => $cycle->progress_percentage,
            'current_week' => $cycle->current_week,
            'completed_workouts_count' => $cycle->completed_workouts_count,
            'plans_count' => $plansCount,
            'plans' => $plans,
            'workouts' => $workouts,
            'total_workouts' => $cycle->workouts->count(),
            'completed_workouts' => $completedWorkouts,
            'total_scheduled_workouts' => $totalScheduledWorkouts,
            'created_at' => $cycle->created_at?->toISOString(),
            'updated_at' => $cycle->updated_at?->toISOString(),
        ];
    }

    /**
     * Получить структурные данные цикла для экспорта (только структура без статистики)
     * 
     * @param Cycle $cycle Цикл для экспорта
     * 
     * @return array Массив со структурными данными цикла
     */
    public function getStructureData(Cycle $cycle): array
    {
        $cycle->load([
            'plans.planExercises.exercise.muscleGroup'
        ]);

        $plans = $cycle->plans->map(function ($plan) {
            $plan->load('planExercises.exercise.muscleGroup');
            
            return [
                'name' => $plan->name,
                'order' => $plan->order,
                'exercises' => $plan->planExercises->map(function ($planExercise) {
                    return [
                        'name' => $planExercise->exercise->name,
                        'description' => $planExercise->exercise->description,
                        'muscle_group' => $planExercise->exercise->muscleGroup?->name,
                        'order' => $planExercise->order,
                    ];
                })->toArray(),
            ];
        })->toArray();

        return [
            'name' => $cycle->name,
            'weeks' => $cycle->weeks,
            'plans' => $plans,
        ];
    }
}

