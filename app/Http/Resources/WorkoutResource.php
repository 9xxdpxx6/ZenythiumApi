<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API ресурс для тренировок
 * 
 * Преобразует модель тренировки в массив для JSON ответа API.
 * Включает основную информацию о тренировке, связанные данные плана и пользователя.
 */
final class WorkoutResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для JSON ответа
     * 
     * @param Request $request HTTP запрос
     * 
     * @return array Массив с данными тренировки:
     * - id: ID тренировки
     * - started_at: время начала в ISO 8601 формате
     * - finished_at: время окончания в ISO 8601 формате
     * - duration_minutes: продолжительность в минутах (вычисляемый атрибут)
     * - exercise_count: количество упражнений (вычисляемый атрибут)
     * - total_volume: общий объем тренировки (вычисляемый атрибут)
     * - plan: данные плана тренировки (id, name)
     * - user: данные пользователя (id, name)
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
