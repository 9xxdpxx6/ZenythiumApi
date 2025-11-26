<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="CycleResource",
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
 *     @OA\Property(property="current_week", type="integer", example=3, description="Текущая неделя цикла, рассчитывается на основе прогресса выполнения тренировок (синхронизировано с progress_percentage). Значение от 0 до weeks."),
 *     @OA\Property(property="completed_workouts_count", type="integer", example=8),
 *     @OA\Property(property="plans_count", type="integer", example=3),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 */
final class CycleResource extends JsonResource
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
            'current_week' => $this->current_week,
            'completed_workouts_count' => $this->completed_workouts_count,
            'plans_count' => $this->whenLoaded('plans', function () {
                return $this->plans->count();
            }, function () {
                return $this->plans_count ?? 0;
            }),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
