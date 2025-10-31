<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingProgramInstallation;
use App\Services\TrainingProgramService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="TrainingProgramDetailResource",
 *     type="object",
 *     description="Детальная информация о программе тренировок со структурой",
 *     @OA\Property(property="id", type="integer", example=1, description="Уникальный идентификатор программы"),
 *     @OA\Property(property="name", type="string", example="Beginner", description="Название программы"),
 *     @OA\Property(property="description", type="string", nullable=true, example="Программа тренировок: Beginner", description="Описание программы"),
 *     @OA\Property(property="author", type="object", nullable=true, description="Автор программы",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Иван Петров")
 *     ),
 *     @OA\Property(property="duration_weeks", type="integer", example=5, description="Продолжительность программы в неделях"),
 *     @OA\Property(property="is_active", type="boolean", example=true, description="Активна ли программа"),
 *     @OA\Property(property="is_installed", type="boolean", example=false, description="Установлена ли программа у текущего пользователя"),
 *     @OA\Property(property="structure", type="object", nullable=true, description="Структура программы: циклы, планы и упражнения",
 *         @OA\Property(property="cycles", type="array", @OA\Items(type="object",
 *             @OA\Property(property="name", type="string", example="Базовый цикл для новичков", description="Название цикла"),
 *             @OA\Property(property="plans", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="name", type="string", example="День 1: Грудь и трицепс", description="Название плана"),
 *                 @OA\Property(property="exercises", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="name", type="string", example="Жим лежа", description="Название упражнения"),
 *                     @OA\Property(property="muscle_group_id", type="integer", example=1, description="ID группы мышц"),
 *                     @OA\Property(property="description", type="string", nullable=true, example="Базовое упражнение для развития грудных мышц", description="Описание упражнения")
 *                 ), description="Список упражнений в плане")
 *             ), description="Список планов в цикле")
 *         ), description="Список циклов программы")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Дата создания"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Дата обновления")
 * )
 */
final class TrainingProgramDetailResource extends JsonResource
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

        // Получаем структуру программы из сидера
        $programService = app(TrainingProgramService::class);
        $programData = $programService->getProgramStructure($this->id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'author' => $this->author ? [
                'id' => $this->author->id,
                'name' => $this->author->name,
            ] : null,
            'duration_weeks' => $this->duration_weeks,
            'is_active' => $this->is_active,
            'is_installed' => $isInstalled,
            'structure' => $programData ?? null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

