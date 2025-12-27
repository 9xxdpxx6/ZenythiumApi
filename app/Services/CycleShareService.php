<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cycle;
use App\Models\SharedCycle;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Сервис для работы с расшаренными циклами тренировок
 */
final class CycleShareService
{
    /**
     * Генерировать или получить существующую ссылку для расшаривания цикла
     * 
     * @param int $cycleId ID цикла
     * @param int $userId ID пользователя
     * 
     * @return string Полная ссылка для расшаривания
     * 
     * @throws \Exception Если цикл не найден или не принадлежит пользователю
     */
    public function generateShareLink(int $cycleId, int $userId): string
    {
        $cycle = Cycle::where('id', $cycleId)
            ->where('user_id', $userId)
            ->first();

        if (!$cycle) {
            throw new \Exception('Цикл не найден или вы не имеете прав на его расшаривание');
        }

        // Используем транзакцию с блокировкой для предотвращения race conditions
        return DB::transaction(function () use ($cycle) {
            // Проверяем, существует ли уже shared_cycle для этого цикла
            $sharedCycle = SharedCycle::where('cycle_id', $cycle->id)
                ->lockForUpdate()
                ->first();

            if ($sharedCycle) {
                // Возвращаем существующую ссылку
                return $this->buildShareUrl($sharedCycle->share_id);
            }

            // Генерируем новый share_id
            $shareId = (string) Str::uuid();

            // Создаем запись в shared_cycles
            $sharedCycle = SharedCycle::create([
                'cycle_id' => $cycle->id,
                'share_id' => $shareId,
                'view_count' => 0,
                'import_count' => 0,
                'is_active' => true,
                'expires_at' => null,
            ]);

            return $this->buildShareUrl($shareId);
        });
    }

    /**
     * Получить SharedCycle по share_id
     * 
     * @param string $shareId UUID ссылки
     * 
     * @return SharedCycle|null SharedCycle или null если не найдена или недоступна
     */
    public function getSharedCycle(string $shareId): ?SharedCycle
    {
        $sharedCycle = SharedCycle::where('share_id', $shareId)
            ->with(['cycle'])
            ->first();

        if (!$sharedCycle) {
            return null;
        }

        // Проверяем доступность
        if (!$sharedCycle->isAccessible()) {
            return null;
        }

        return $sharedCycle;
    }

    /**
     * Получить данные программы для импорта
     * 
     * @param string $shareId UUID ссылки
     * 
     * @return array|null Данные программы (структура: cycles, plans, exercises) или null
     */
    public function getSharedCycleData(string $shareId): ?array
    {
        $cacheKey = "shared_cycle_data_{$shareId}";

        return Cache::remember($cacheKey, 3600, function () use ($shareId) {
            $sharedCycle = $this->getSharedCycle($shareId);

            if (!$sharedCycle || !$sharedCycle->cycle) {
                return null;
            }

            $cycle = $sharedCycle->cycle;

            // Формируем структуру в нужном формате
            $structure = [
                'cycles' => [
                    [
                        'name' => $cycle->name,
                        'plans' => [],
                    ],
                ],
            ];

            // Загружаем планы с упражнениями
            $plans = $cycle->plans()->with(['planExercises.exercise.muscleGroup'])->orderBy('order')->get();

            foreach ($plans as $plan) {
                $planData = [
                    'name' => $plan->name,
                    'exercises' => [],
                ];

                foreach ($plan->planExercises as $planExercise) {
                    $exercise = $planExercise->exercise;
                    $exerciseData = [
                        'name' => $exercise->name,
                        'description' => $exercise->description,
                    ];

                    // Добавляем объект muscle_group с id и name, если группа мышц существует
                    if ($exercise->muscleGroup) {
                        $exerciseData['muscle_group'] = [
                            'id' => $exercise->muscleGroup->id,
                            'name' => $exercise->muscleGroup->name,
                        ];
                    } else {
                        $exerciseData['muscle_group'] = null;
                    }

                    $planData['exercises'][] = $exerciseData;
                }

                $structure['cycles'][0]['plans'][] = $planData;
            }

            return $structure;
        });
    }

    /**
     * Инкрементировать счетчик просмотров
     * 
     * @param string $shareId UUID ссылки
     * 
     * @return void
     */
    public function incrementViewCount(string $shareId): void
    {
        SharedCycle::where('share_id', $shareId)->increment('view_count');
    }

    /**
     * Инкрементировать счетчик импортов
     * 
     * @param string $shareId UUID ссылки
     * 
     * @return void
     */
    public function incrementImportCount(string $shareId): void
    {
        SharedCycle::where('share_id', $shareId)->increment('import_count');
    }

    /**
     * Построить полный URL для расшаривания
     * 
     * @param string $shareId UUID ссылки
     * 
     * @return string Полный URL
     */
    private function buildShareUrl(string $shareId): string
    {
        $frontendUrl = config('app.frontend_url', config('app.url'));
        $apiUrl = config('app.url');

        // Если frontend_url задан и отличается от api_url, используем его
        if ($frontendUrl && $frontendUrl !== $apiUrl) {
            return rtrim($frontendUrl, '/') . '/shared-cycles/' . $shareId;
        }

        // Иначе используем API URL
        return rtrim($apiUrl, '/') . '/api/v1/shared-cycles/' . $shareId;
    }
}

