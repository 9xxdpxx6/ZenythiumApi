<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingProgramInstallation;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TrainingProgramResource",
 *     type="object",
 *     description="Ресурс для списка программ тренировок из каталога",
 *     @OA\Property(property="id", type="integer", example=1, description="Уникальный идентификатор программы"),
 *     @OA\Property(property="name", type="string", example="Beginner", description="Название программы"),
 *     @OA\Property(property="author", type="object", nullable=true, description="Автор программы",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Иван Петров")
 *     ),
 *     @OA\Property(property="duration_weeks", type="integer", example=5, description="Продолжительность программы в неделях"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Активна ли программа"),
 *     @OA\Property(property="is_installed", type="boolean", example=false, description="Установлена ли программа у текущего пользователя"),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Дата создания"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Дата обновления")
 * )
 */
final class TrainingProgramResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $userId = $request->user()?->id;
        $isInstalled = false;

        if ($userId) {
            $isInstalled = TrainingProgramInstallation::where('user_id', $userId)
                ->where('training_program_id', $this->id)
                ->exists();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'author' => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ] : null,
            'duration_weeks' => $this->duration_weeks,
            'is_active' => $this->is_active,
            'is_installed' => $isInstalled,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

