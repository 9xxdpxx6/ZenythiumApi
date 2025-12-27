<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Services\CycleShareService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="SharedCycleResource",
 *     type="object",
 *     description="Данные расшаренного цикла тренировок для предпросмотра",
 *     @OA\Property(property="id", type="integer", example=1, description="ID цикла"),
 *     @OA\Property(property="name", type="string", example="Программа на массу", description="Название цикла"),
 *     @OA\Property(property="weeks", type="integer", example=12, description="Продолжительность цикла в неделях"),
 *     @OA\Property(property="author", type="object", nullable=true, description="Автор цикла",
 *         @OA\Property(property="id", type="integer", example=1),
 *         @OA\Property(property="name", type="string", example="Иван Петров")
 *     ),
 *     @OA\Property(property="plans_count", type="integer", example=3, description="Количество планов"),
 *     @OA\Property(property="exercises_count", type="integer", example=15, description="Общее количество упражнений"),
 *     @OA\Property(property="view_count", type="integer", example=5, description="Количество просмотров"),
 *     @OA\Property(property="import_count", type="integer", example=2, description="Количество импортов"),
 *     @OA\Property(property="structure", type="object", nullable=true, description="Структура цикла: планы и упражнения",
 *         @OA\Property(property="cycles", type="array", @OA\Items(type="object",
 *             @OA\Property(property="name", type="string", example="Программа на массу", description="Название цикла"),
 *             @OA\Property(property="plans", type="array", @OA\Items(type="object",
 *                 @OA\Property(property="name", type="string", example="День 1: Грудь и трицепс", description="Название плана"),
 *                 @OA\Property(property="exercises", type="array", @OA\Items(type="object",
 *                     @OA\Property(property="name", type="string", example="Жим лежа", description="Название упражнения"),
 *                     @OA\Property(property="muscle_group", type="object", nullable=true,
 *                         @OA\Property(property="id", type="integer", example=1),
 *                         @OA\Property(property="name", type="string", example="Грудь")
 *                     ),
 *                     @OA\Property(property="description", type="string", nullable=true, example="Базовое упражнение", description="Описание упражнения")
 *                 ), description="Список упражнений в плане")
 *             ), description="Список планов в цикле")
 *         ), description="Список циклов")
 *     ),
 *     @OA\Property(property="created_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Дата создания"),
 *     @OA\Property(property="updated_at", type="string", format="date-time", example="2024-01-01T00:00:00.000000Z", description="Дата обновления")
 * )
 */
final class SharedCycleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $cycle = $this->cycle;
        $shareService = app(CycleShareService::class);
        $structure = $shareService->getSharedCycleData($this->share_id);

        // Подсчитываем количество упражнений
        $exercisesCount = 0;
        if ($structure && isset($structure['cycles'][0]['plans'])) {
            foreach ($structure['cycles'][0]['plans'] as $plan) {
                $exercisesCount += count($plan['exercises'] ?? []);
            }
        }

        return [
            'id' => $cycle->id ?? null,
            'name' => $cycle->name ?? null,
            'weeks' => $cycle->weeks ?? null,
            'author' => $cycle->user ? [
                'id' => $cycle->user->id,
                'name' => $cycle->user->name,
            ] : null,
            'plans_count' => $structure ? count($structure['cycles'][0]['plans'] ?? []) : 0,
            'exercises_count' => $exercisesCount,
            'view_count' => $this->view_count,
            'import_count' => $this->import_count,
            'structure' => $structure,
            'created_at' => $cycle->created_at?->toISOString(),
            'updated_at' => $cycle->updated_at?->toISOString(),
        ];
    }
}

