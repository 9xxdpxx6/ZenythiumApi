<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * API ресурс для упражнений
 * 
 * Преобразует модель упражнения в массив для JSON ответа API.
 * Включает основную информацию об упражнении и связанные данные группы мышц.
 */
final class ExerciseResource extends JsonResource
{
    /**
     * Преобразовать ресурс в массив для JSON ответа
     * 
     * @param Request $request HTTP запрос
     * 
     * @return array Массив с данными упражнения:
     * - id: ID упражнения
     * - name: название упражнения
     * - description: описание упражнения
     * - user_id: ID пользователя-создателя
     * - muscle_group: данные группы мышц (id, name)
     * - is_active: статус активности
     * - created_at: время создания в ISO 8601 формате
     * - updated_at: время обновления в ISO 8601 формате
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'user_id' => $this->user_id,
            'muscle_group' => [
                'id' => $this->muscleGroup->id,
                'name' => $this->muscleGroup->name,
            ],
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
