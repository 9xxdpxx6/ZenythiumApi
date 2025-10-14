<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="PlanDetailResource",
 *     type="object",
 *     @OA\Property(property="id", type="integer", example=1),
 *     @OA\Property(property="name", type="string", example="Силовая тренировка"),
 *     @OA\Property(property="order", type="integer", example=1),
 *     @OA\Property(property="is_active", type="boolean", example=true),
 *     @OA\Property(property="exercise_count", type="integer", example=5),
 *     @OA\Property(property="cycle", type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Программа на массу")
 *     ),
 *     @OA\Property(property="exercises", type="array", items=@OA\Items(type="object",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="order", type="integer", example=1),
 *         @OA\Property(property="exercise", type="object",
 *             @OA\Property(property="id", type="integer", example=1),
 *             @OA\Property(property="name", type="string", example="Жим лежа"),
 *             @OA\Property(property="description", type="string", example="Базовое упражнение"),
 *             @OA\Property(property="muscle_group", type="object",
 *                 @OA\Property(property="id", type="integer", example=1),
 *                 @OA\Property(property="name", type="string", example="Грудь")
 *             )
 *         )
 *     )),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z")
 * )
 */
final class PlanDetailResource extends JsonResource
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
            'cycle' => $this->cycle ? [
                'id' => $this->cycle->id,
                'name' => $this->cycle->name,
            ] : null,
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
