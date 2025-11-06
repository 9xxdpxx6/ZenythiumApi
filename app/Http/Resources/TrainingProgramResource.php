<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\TrainingProgramInstallation;
use App\Services\TrainingProgramService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

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
 *     @OA\Property(property="install_id", type="integer", nullable=true, example=1, description="ID установки программы для текущего пользователя (если установлена)"),
 *     @OA\Property(property="installations_count", type="integer", example=5, description="Общее количество установок программы"),
 *     @OA\Property(property="cycles_count", type="integer", example=1, description="Количество циклов в программе"),
 *     @OA\Property(property="plans_count", type="integer", example=3, description="Количество планов в программе"),
 *     @OA\Property(property="exercises_count", type="integer", example=15, description="Количество упражнений в программе"),
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
        $installId = null;

        if ($userId) {
            // Используем предзагруженные установки пользователя из коллекции, если доступны
            // Это предотвращает N+1 запросы
            // Формат: [program_id => install_id]
            $userInstallations = $request->input('_user_installations', []);
            
            if (empty($userInstallations)) {
                // Если установки не предзагружены, делаем один запрос (для случая детального просмотра)
                $installation = TrainingProgramInstallation::where('user_id', $userId)
                    ->where('training_program_id', $this->id)
                    ->first();
                
                if ($installation) {
                    $isInstalled = true;
                    $installId = $installation->id;
                }
            } else {
                // Проверяем в памяти по предзагруженным данным
                if (isset($userInstallations[$this->id])) {
                    $isInstalled = true;
                    $installId = $userInstallations[$this->id];
                }
            }
        }

        // Получаем counts для программы (кэшируются, так как структура редко меняется)
        $cacheKey = "training_program_counts_{$this->id}";
        
        $counts = Cache::remember($cacheKey, 3600, function () {
            $programService = app(TrainingProgramService::class);
            $programStructure = $programService->getProgramStructure($this->id);

            $cyclesCount = 0;
            $plansCount = 0;
            $exercisesCount = 0;

            if ($programStructure && isset($programStructure['cycles'])) {
                $cyclesCount = count($programStructure['cycles']);
                foreach ($programStructure['cycles'] as $cycle) {
                    $plans = $cycle['plans'] ?? [];
                    $plansCount += count($plans);
                    foreach ($plans as $plan) {
                        $exercises = $plan['exercises'] ?? [];
                        $exercisesCount += count($exercises);
                    }
                }
            }
            
            return [
                'cycles_count' => $cyclesCount,
                'plans_count' => $plansCount,
                'exercises_count' => $exercisesCount,
            ];
        });

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
            'install_id' => $installId,
            'installations_count' => $this->installs_count ?? $this->whenLoaded('installs', function () {
                return $this->installs->count();
            }, function () {
                return $this->installs()->count();
            }),
            'cycles_count' => $counts['cycles_count'],
            'plans_count' => $counts['plans_count'],
            'exercises_count' => $counts['exercises_count'],
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}

