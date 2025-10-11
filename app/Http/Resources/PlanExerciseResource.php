<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class PlanExerciseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order' => $this->order,
            'exercise' => [
                'id' => $this->exercise->id,
                'name' => $this->exercise->name,
                'description' => $this->exercise->description,
                'muscle_group' => [
                    'id' => $this->exercise->muscleGroup->id,
                    'name' => $this->exercise->muscleGroup->name,
                ],
            ],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
