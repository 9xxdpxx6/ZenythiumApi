<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\SharedCycle;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Сервис для импорта расшаренных циклов тренировок
 */
final class CycleImportService
{
    private const MAX_PLANS = 100;
    private const MAX_EXERCISES = 500;

    public function __construct(
        private readonly CycleShareService $shareService,
        private readonly ExerciseResolutionService $exerciseResolutionService
    ) {}

    /**
     * Импортировать цикл из расшаренной ссылки
     * 
     * @param string $shareId UUID ссылки
     * @param int $userId ID пользователя-получателя
     * 
     * @return array Массив с информацией о созданных элементах:
     *   - cycle: Cycle
     *   - plans: Collection<Plan>
     *   - exercises: Collection<Exercise>
     * 
     * @throws \Exception При ошибке импорта
     */
    public function importFromShare(string $shareId, int $userId): array
    {
        // Получаем shared_cycle
        $sharedCycle = $this->shareService->getSharedCycle($shareId);

        if (!$sharedCycle) {
            throw new \Exception('Расшаренный цикл не найден или недоступен');
        }

        $sourceCycle = $sharedCycle->cycle;

        if (!$sourceCycle) {
            throw new \Exception('Исходный цикл не найден');
        }

        // Проверяем, что пользователь не пытается импортировать свою программу
        if ($sourceCycle->user_id === $userId) {
            throw new \Exception('Нельзя импортировать свою собственную программу');
        }

        // Получаем данные программы
        $cycleData = $this->shareService->getSharedCycleData($shareId);

        if (!$cycleData || empty($cycleData['cycles'])) {
            throw new \Exception('Данные программы не найдены');
        }

        $cycleData = $cycleData['cycles'][0]; // Пока поддерживаем один цикл

        // Валидируем размер программы
        $plansCount = count($cycleData['plans'] ?? []);
        $exercisesCount = 0;
        foreach ($cycleData['plans'] ?? [] as $planData) {
            $exercisesCount += count($planData['exercises'] ?? []);
        }

        if ($plansCount > self::MAX_PLANS) {
            throw new \Exception("Программа слишком большая: максимальное количество планов - " . self::MAX_PLANS);
        }

        if ($exercisesCount > self::MAX_EXERCISES) {
            throw new \Exception("Программа слишком большая: максимальное количество упражнений - " . self::MAX_EXERCISES);
        }

        // Предзагружаем данные для оптимизации
        $userExercises = Exercise::where('user_id', $userId)->get();
        $userCycleNames = Cycle::where('user_id', $userId)->pluck('name');
        $userPlanNames = Plan::where('user_id', $userId)->pluck('name');

        DB::beginTransaction();

        try {
            $createdExercises = collect();
            $createdPlans = collect();
            $createdCycle = null;

            // Создаем цикл
            $cycleName = $this->exerciseResolutionService->resolveUniqueName(
                Cycle::class,
                $cycleData['name'],
                $userId,
                $userCycleNames
            );

            $createdCycle = Cycle::create([
                'user_id' => $userId,
                'name' => $cycleName,
                'start_date' => now()->toDateString(),
                'end_date' => null,
                'weeks' => $sourceCycle->weeks,
            ]);

            // Создаем планы в цикле
            foreach ($cycleData['plans'] ?? [] as $planIndex => $planData) {
                $planName = $this->exerciseResolutionService->resolveUniqueName(
                    Plan::class,
                    $planData['name'],
                    $userId,
                    $userPlanNames
                );

                $plan = Plan::create([
                    'user_id' => $userId,
                    'cycle_id' => $createdCycle->id,
                    'name' => $planName,
                    'order' => $planIndex + 1,
                    'is_active' => true,
                ]);

                $createdPlans->push($plan);

                // Создаем упражнения для плана
                foreach ($planData['exercises'] ?? [] as $exerciseIndex => $exerciseData) {
                    $exercise = $this->exerciseResolutionService->resolveOrCreateExercise(
                        $exerciseData,
                        $userId,
                        $userExercises
                    );

                    // Создаем связь упражнения с планом
                    PlanExercise::create([
                        'plan_id' => $plan->id,
                        'exercise_id' => $exercise->id,
                        'order' => $exerciseIndex + 1,
                    ]);

                    if (!$createdExercises->contains('id', $exercise->id)) {
                        $createdExercises->push($exercise);
                    }
                }
            }

            // Инкрементируем счетчик импортов
            $this->shareService->incrementImportCount($shareId);

            DB::commit();

            // Логируем успешный импорт
            Log::info('Cycle imported', [
                'share_id' => $shareId,
                'user_id' => $userId,
                'imported_cycle_id' => $createdCycle->id,
                'plans_count' => $createdPlans->count(),
                'exercises_count' => $createdExercises->count(),
            ]);

            return [
                'cycle' => $createdCycle,
                'plans' => $createdPlans,
                'exercises' => $createdExercises,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import failed', [
                'share_id' => $shareId,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

