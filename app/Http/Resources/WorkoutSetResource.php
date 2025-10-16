<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkoutSetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_id' => $this->workout_id,
            'plan_exercise_id' => $this->plan_exercise_id,
            'weight' => $this->weight,
            'reps' => $this->reps,
            'workout' => [
                'id' => $this->workout->id,
                'started_at' => $this->workout->started_at?->toISOString(),
                'finished_at' => $this->workout->finished_at?->toISOString(),
                'duration_minutes' => $this->workout->duration_minutes,
                'exercise_count' => $this->workout->exercise_count,
                'total_volume' => $this->workout->total_volume,
                'plan' => [
                    'id' => $this->workout->plan->id,
                    'name' => $this->workout->plan->name,
                ],
                'user' => [
                    'id' => $this->workout->user->id,
                    'name' => $this->workout->user->name,
                ],
            ],
            'plan_exercise' => [
                'id' => $this->planExercise->id,
                'order' => $this->planExercise->order,
                'exercise' => [
                    'id' => $this->planExercise->exercise->id,
                    'name' => $this->planExercise->exercise->name,
                    'description' => $this->planExercise->exercise->description,
                ],
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
