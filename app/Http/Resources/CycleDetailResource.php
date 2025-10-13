<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CycleDetailResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Программа на массу"),
 *     @OA\Property(property="user", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Иван Петров")
 *     ),
 *     @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2024-12-31"),
 *     @OA\Property(property="weeks", type="integer", example=12),
 *     @OA\Property(property="progress_percentage", type="number", format="float", example=75.5),
 *     @OA\Property(property="completed_workouts_count", type="integer", example=8),
 *     @OA\Property(property="plans_count", type="integer", example=3),
 *     @OA\Property(property="plans", type="array", items=@OA\Items(type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Силовая тренировка"),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="is_active", type="boolean", example=true),
 *         @OA\Property(property="exercise_count", type="integer", example=5),
 *         @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *         @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 *     )),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 */
final class CycleDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
            ],
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'weeks' => $this->weeks,
            'progress_percentage' => $this->progress_percentage,
            'completed_workouts_count' => $this->completed_workouts_count,
            'plans_count' => $this->whenLoaded('plans', function () {
                return $this->plans->count();
            }, function () {
                return $this->plans_count ?? 0;
            }),
            'plans' => $this->whenLoaded('plans', function () {
                return $this->plans->map(function ($plan) {
                    return [
                        'id' => $plan->id,
                        'name' => $plan->name,
                        'order' => $plan->order,
                        'is_active' => $plan->is_active,
                        'exercise_count' => $plan->exercise_count,
                        'created_at' => $plan->created_at?->toISOString(),
                        'updated_at' => $plan->updated_at?->toISOString(),
                    ];
                });
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

