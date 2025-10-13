<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlanResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'order' => $this->order,
            'is_active' => $this->is_active,
            'exercise_count' => $this->exercise_count,
            'cycle' => [
                'id' => $this->cycle->id,
                'name' => $this->cycle->name,
            ],
            'exercises' => $this->whenLoaded('planExercises', function () {
                return $this->planExercises->map(function ($planExercise) {
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
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
