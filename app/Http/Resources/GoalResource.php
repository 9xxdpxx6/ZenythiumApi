<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
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
