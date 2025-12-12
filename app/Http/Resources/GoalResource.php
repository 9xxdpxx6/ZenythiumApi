<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="GoalResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="type", type="string", example="total_workouts"),
 *     @OA\Property(property="title", type="string", example="50 тренировок за 3 месяца"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Описание цели"),
 *     @OA\Property(property="target_value", type="number", format="float", example=50.0),
 *     @OA\Property(property="current_value", type="number", format="float", nullable=true, example=25.0),
 *     @OA\Property(property="progress_percentage", type="number", format="float", nullable=true, example=50.0),
 *     @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", nullable=true, example="2024-03-31"),
 *     @OA\Property(property="exercise", type="object", nullable=true,
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Жим лежа")
 *     ),
 *     @OA\Property(property="status", type="string", example="active"),
 *     @OA\Property(property="completed_at", type="string", format="date-time", nullable=true, example="2024-03-31T23:59:59.000000Z"),
 *     @OA\Property(property="achieved_value", type="number", format="float", nullable=true, example=50.0),
 *     @OA\Property(property="days_to_complete", type="integer", nullable=true, example=15),
 *     @OA\Property(property="created_at", type="string", format="date-time", nullable=true, example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", nullable=true, example="2024-01-01T00:00:00.000000Z")
 * )
 *
 * API ресурс для целей
 *
 * Преобразует модель цели в массив для JSON ответа API.
 */
final class GoalResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для JSON ответа
     *
     * @param Request $request HTTP запрос
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'title' => $this->title,
            'description' => $this->description,
            'target_value' => (float) $this->target_value,
            'current_value' => $this->current_value ? (float) $this->current_value : null,
            'progress_percentage' => $this->progress_percentage,
            'start_date' => $this->start_date->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'exercise' => $this->when($this->exercise_id, function () {
                return [
                    'id' => $this->exercise->id,
                    'name' => $this->exercise->name,
                ];
            }),
            'status' => $this->status->value,
            'completed_at' => $this->completed_at?->toISOString(),
            'achieved_value' => $this->achieved_value ? (float) $this->achieved_value : null,
            'days_to_complete' => $this->days_to_complete,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
