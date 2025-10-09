<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class WorkoutResource extends JsonResource
{
    /**
     * Transform the resource into an array.
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
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
