<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class StatisticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/statistics",
     *     summary="Получение статистики пользователя",
     *     description="Возвращает комплексную статистику тренировок и прогресса пользователя. Включает общее количество тренировок, время тренировок, объемы, изменения веса и частоту тренировок",
     *     tags={"Statistics"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Статистика пользователя успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_workouts", type="integer", example=45),
     *                 @OA\Property(property="completed_workouts", type="integer", example=42),
     *                 @OA\Property(property="total_training_time", type="integer", example=3150),
     *                 @OA\Property(property="total_volume", type="string", example="125000.00"),
     *                 @OA\Property(property="current_weight", type="number", format="float", example=75.5),
     *                 @OA\Property(property="active_cycles_count", type="integer", example=2),
     *                 @OA\Property(property="weight_change_30_days", type="number", example=2),
     *                 @OA\Property(property="training_frequency_4_weeks", type="number", example=3),
     *                 @OA\Property(property="training_streak_days", type="integer", example=12, description="Количество подряд выполненных тренировок без пропусков в циклах")
     *             ),
     *             @OA\Property(property="message", type="string", example="Статистика пользователя успешно получена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь не аутентифицирован")
     *         )
     *     )
     * )
     */
    public function statistics(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $user = User::findOrFail($userId);

        // Basic workout statistics
        $totalWorkouts = $user->workouts()->count();
        $completedWorkouts = $user->workouts()->whereNotNull('finished_at')->count();
        
        // Total training time in minutes
        $totalTrainingTime = $user->workouts()
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->get()
            ->sum('duration_minutes');

        // Total volume (weight × reps)
        $totalVolume = $user->workoutSets()
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->sum(DB::raw('weight * reps'));

        // Current weight from latest metric
        $currentWeight = $user->current_weight;

        // Active cycles count
        $activeCyclesCount = $user->cycles()
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->count();

        // Weight change over time (last 30 days)
        $weightChange = $this->getWeightChange($userId);

        // Training frequency (workouts per week in last 4 weeks)
        $trainingFrequency = $this->getTrainingFrequency($userId);

        // Training streak (consecutive days with workouts)
        $trainingStreak = $this->getTrainingStreak($userId);

        return response()->json([
            'data' => [
                'total_workouts' => $totalWorkouts,
                'completed_workouts' => $completedWorkouts,
                'total_training_time' => $totalTrainingTime,
                'total_volume' => $totalVolume,
                'current_weight' => $currentWeight,
                'active_cycles_count' => $activeCyclesCount,
                'weight_change_30_days' => $weightChange,
                'training_frequency_4_weeks' => $trainingFrequency,
                'training_streak_days' => $trainingStreak,
            ],
            'message' => 'Статистика пользователя успешно получена'
        ]);
    }

    /**
     * Get weight change over last 30 days.
     */
    private function getWeightChange(int $userId): ?float
    {
        $metrics = User::find($userId)
            ->metrics()
            ->where('date', '>=', now()->subDays(30))
            ->orderBy('date')
            ->get();

        if ($metrics->count() < 2) {
            return null;
        }

        $firstWeight = $metrics->first()->weight;
        $lastWeight = $metrics->last()->weight;

        return $lastWeight - $firstWeight;
    }

    /**
     * Get training frequency (workouts per week) over last 4 weeks.
     */
    private function getTrainingFrequency(int $userId): float
    {
        $workouts = User::find($userId)
            ->workouts()
            ->whereNotNull('finished_at')
            ->where('finished_at', '>=', now()->subWeeks(4))
            ->get();

        // Group by week manually for SQLite compatibility
        $weeks = [];
        foreach ($workouts as $workout) {
            $week = $workout->finished_at->format('Y-W');
            $weeks[$week] = ($weeks[$week] ?? 0) + 1;
        }

        // Делим на 4 недели, а не на количество недель с тренировками
        // Это дает правильное среднее количество тренировок в неделю за последние 4 недели
        $totalWorkouts = array_sum($weeks);
        $avgWorkoutsPerWeek = $totalWorkouts / 4;
        return round($avgWorkoutsPerWeek, 1);
    }

    /**
     * Get training streak (consecutive workouts without gaps in cycles).
     * 
     * Пропуск определяется как неполное выполнение всех планов в цикле за неделю.
     * Например, если в цикле 4 плана, а выполнено только 3 - это пропуск.
     */
    private function getTrainingStreak(int $userId): int
    {
        // Получаем все активные циклы пользователя
        $cycles = User::find($userId)
            ->cycles()
            ->with(['plans' => function($query) {
                $query->where('is_active', true)->orderBy('order');
            }])
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->get();

        if ($cycles->isEmpty()) {
            return 0;
        }

        $maxStreak = 0;
        
        foreach ($cycles as $cycle) {
            $cycleStreak = $this->calculateCycleStreak($cycle);
            $maxStreak = max($maxStreak, $cycleStreak);
        }

        return $maxStreak;
    }

    /**
     * Calculate streak for a specific cycle.
     */
    private function calculateCycleStreak(Cycle $cycle): int
    {
        $plans = $cycle->plans->where('is_active', true);
        if ($plans->isEmpty()) {
            return 0;
        }

        $planIds = $plans->pluck('id')->toArray();
        $expectedPlansPerWeek = count($planIds);

        // Получаем все завершенные тренировки этого цикла, сгруппированные по неделям
        $workouts = $cycle->workouts()
            ->whereNotNull('finished_at')
            ->whereIn('plan_id', $planIds)
            ->orderBy('finished_at')
            ->get();

        if ($workouts->isEmpty()) {
            return 0;
        }

        // Группируем тренировки по неделям
        $weeklyWorkouts = [];
        foreach ($workouts as $workout) {
            $weekKey = $workout->finished_at->format('Y-W');
            if (!isset($weeklyWorkouts[$weekKey])) {
                $weeklyWorkouts[$weekKey] = [];
            }
            $weeklyWorkouts[$weekKey][] = $workout;
        }

        // Сортируем недели по дате
        ksort($weeklyWorkouts);

        $currentStreak = 0;
        $previousWeekDate = null;

        foreach ($weeklyWorkouts as $weekKey => $weekWorkouts) {
            // Получаем дату начала текущей недели из первой тренировки
            $currentWeekDate = \Carbon\Carbon::parse($weekWorkouts[0]->finished_at)->startOfWeek();
            
            // Проверяем, что недели идут подряд без пропусков
            if ($previousWeekDate !== null) {
                $weeksDiff = $previousWeekDate->diffInWeeks($currentWeekDate);
                
                // Если между неделями есть пропуск (больше 1 недели), сбрасываем streak
                if ($weeksDiff > 1) {
                    $currentStreak = 0;
                }
            }

            // Получаем уникальные планы, выполненные на этой неделе
            $completedPlans = collect($weekWorkouts)
                ->pluck('plan_id')
                ->unique()
                ->values()
            ->toArray();

            // Проверяем, выполнены ли все планы цикла на этой неделе
            $allPlansCompleted = count($completedPlans) === $expectedPlansPerWeek 
                && empty(array_diff($planIds, $completedPlans));

            if ($allPlansCompleted) {
                $currentStreak += $expectedPlansPerWeek;
            } else {
                // Пропуск - сбрасываем streak
                $currentStreak = 0;
            }

            $previousWeekDate = $currentWeekDate;
        }

        return $currentStreak;
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/exercise-statistics",
     *     summary="Получение детальной статистики по упражнениям",
     *     description="Возвращает детальную статистику по упражнениям пользователя: топ упражнений, прогресс по весам, количество подходов",
     *     tags={"Statistics"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Статистика по упражнениям успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="top_exercises", type="array", @OA\Items(
     *                     @OA\Property(property="exercise_name", type="string"),
     *                     @OA\Property(property="muscle_group", type="string"),
     *                     @OA\Property(property="total_sets", type="integer"),
     *                     @OA\Property(property="total_volume", type="number"),
     *                     @OA\Property(property="max_weight", type="number"),
     *                     @OA\Property(property="avg_weight", type="number"),
     *                     @OA\Property(property="last_performed", type="string", format="date")
     *                 )),
     *                 @OA\Property(property="exercise_progress", type="array", @OA\Items(
     *                     @OA\Property(property="exercise_name", type="string"),
     *                     @OA\Property(property="muscle_group", type="string"),
     *                     @OA\Property(property="weight_progression", type="array", @OA\Items(
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="max_weight", type="number"),
     *                         @OA\Property(property="total_volume", type="number")
     *                     ))
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Статистика по упражнениям успешно получена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь не аутентифицирован")
     *         )
     *     )
     * )
     */
    public function exerciseStatistics(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $user = User::findOrFail($userId);

        // Топ-10 самых часто выполняемых упражнений
        $topExercises = $this->getTopExercises($userId);

        // Прогресс по упражнениям за последние 3 месяца
        $exerciseProgress = $this->getExerciseProgress($userId);

        return response()->json([
            'data' => [
                'top_exercises' => $topExercises,
                'exercise_progress' => $exerciseProgress,
            ],
            'message' => 'Статистика по упражнениям успешно получена'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/time-analytics",
     *     summary="Получение временной аналитики тренировок",
     *     description="Возвращает временную аналитику тренировок: графики по дням недели, месяцам, тренды",
     *     tags={"Statistics"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Временная аналитика успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="weekly_pattern", type="array", @OA\Items(
     *                     @OA\Property(property="day_of_week", type="string"),
     *                     @OA\Property(property="workout_count", type="integer"),
     *                     @OA\Property(property="avg_duration", type="number"),
     *                     @OA\Property(property="total_volume", type="number")
     *                 )),
     *                 @OA\Property(property="monthly_trends", type="array", @OA\Items(
     *                     @OA\Property(property="month", type="string"),
     *                     @OA\Property(property="workout_count", type="integer"),
     *                     @OA\Property(property="total_volume", type="number"),
     *                     @OA\Property(property="avg_duration", type="number")
     *                 )),
     *                 @OA\Property(property="volume_trends", type="array", @OA\Items(
     *                     @OA\Property(property="week", type="string"),
     *                     @OA\Property(property="total_volume", type="number"),
     *                     @OA\Property(property="workout_count", type="integer")
     *                 ))
     *             ),
     *             @OA\Property(property="message", type="string", example="Временная аналитика успешно получена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь не аутентифицирован")
     *         )
     *     )
     * )
     */
    public function timeAnalytics(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $user = User::findOrFail($userId);

        // Паттерн тренировок по дням недели
        $weeklyPattern = $this->getWeeklyPattern($userId);

        // Тренды по месяцам за последний год
        $monthlyTrends = $this->getMonthlyTrends($userId);

        // Тренды объема по неделям за последние 12 недель
        $volumeTrends = $this->getVolumeTrends($userId);

        return response()->json([
            'data' => [
                'weekly_pattern' => $weeklyPattern,
                'monthly_trends' => $monthlyTrends,
                'volume_trends' => $volumeTrends,
            ],
            'message' => 'Временная аналитика успешно получена'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/muscle-group-statistics",
     *     summary="Получение статистики по мышечным группам",
     *     description="Возвращает статистику по мышечным группам: объем работы, частота тренировок, дисбаланс",
     *     tags={"Statistics"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Статистика по мышечным группам успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="muscle_group_stats", type="array", @OA\Items(
     *                     @OA\Property(property="muscle_group_name", type="string"),
     *                     @OA\Property(property="size_factor", type="number"),
     *                     @OA\Property(property="optimal_frequency_per_week", type="integer"),
     *                     @OA\Property(property="total_volume", type="number"),
     *                     @OA\Property(property="workout_count", type="integer"),
     *                     @OA\Property(property="exercise_count", type="integer"),
     *                     @OA\Property(property="avg_volume_per_workout", type="number"),
     *                     @OA\Property(property="last_trained", type="string", format="date"),
     *                     @OA\Property(property="first_trained", type="string", format="date"),
     *                     @OA\Property(property="unique_training_days", type="integer"),
     *                     @OA\Property(property="days_since_last_training", type="integer")
     *                 )),
     *                 @OA\Property(property="balance_analysis", type="object",
     *                     @OA\Property(property="most_trained", type="string"),
     *                     @OA\Property(property="least_trained", type="string"),
     *                     @OA\Property(property="balance_score", type="number"),
     *                     @OA\Property(property="recommendations", type="array", @OA\Items(type="string"))
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Статистика по мышечным группам успешно получена")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь не аутентифицирован")
     *         )
     *     )
     * )
     */
    public function muscleGroupStatistics(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $user = User::findOrFail($userId);

        // Статистика по мышечным группам
        $muscleGroupStats = $this->getMuscleGroupStats($userId);

        // Анализ баланса
        $balanceAnalysis = $this->getBalanceAnalysis($muscleGroupStats);

        return response()->json([
            'data' => [
                'muscle_group_stats' => $muscleGroupStats,
                'balance_analysis' => $balanceAnalysis,
            ],
            'message' => 'Статистика по мышечным группам успешно получена'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/records",
     *     summary="Получение рекордов пользователя",
     *     description="Возвращает личные рекорды пользователя: максимальные веса, объемы, лучшие результаты",
     *     tags={"Statistics"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Рекорды пользователя успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="personal_records", type="array", @OA\Items(
     *                     @OA\Property(property="exercise_name", type="string"),
     *                     @OA\Property(property="muscle_group", type="string"),
     *                     @OA\Property(property="max_weight", type="number"),
     *                     @OA\Property(property="max_reps", type="integer"),
     *                     @OA\Property(property="max_volume", type="number"),
     *                     @OA\Property(property="achieved_date", type="string", format="date")
     *                 )),
     *                 @OA\Property(property="workout_records", type="object",
     *                     @OA\Property(property="max_volume_workout", type="object",
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="total_volume", type="number"),
     *                         @OA\Property(property="duration_minutes", type="integer")
     *                     ),
     *                     @OA\Property(property="longest_workout", type="object",
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="duration_minutes", type="integer"),
     *                         @OA\Property(property="total_volume", type="number")
     *                     ),
     *                     @OA\Property(property="most_exercises_workout", type="object",
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="exercise_count", type="integer"),
     *                         @OA\Property(property="total_volume", type="number")
     *                     )
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Рекорды пользователя успешно получены")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Не авторизован",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Пользователь не аутентифицирован")
     *         )
     *     )
     * )
     */
    public function records(Request $request): JsonResponse
    {
        $userId = $request->user()?->id;
        if (!$userId) {
            return response()->json(['message' => 'Пользователь не аутентифицирован'], 401);
        }

        $user = User::findOrFail($userId);

        // Личные рекорды по упражнениям
        $personalRecords = $this->getPersonalRecords($userId);

        // Рекорды тренировок
        $workoutRecords = $this->getWorkoutRecords($userId);

        return response()->json([
            'data' => [
                'personal_records' => $personalRecords,
                'workout_records' => $workoutRecords,
            ],
            'message' => 'Рекорды пользователя успешно получены'
        ]);
    }

    // Приватные методы для получения данных

    private function getTopExercises(int $userId): array
    {
        return DB::table('workout_sets')
            ->join('plan_exercises', 'workout_sets.plan_exercise_id', '=', 'plan_exercises.id')
            ->join('exercises', 'plan_exercises.exercise_id', '=', 'exercises.id')
            ->join('muscle_groups', 'exercises.muscle_group_id', '=', 'muscle_groups.id')
            ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $userId)
            ->whereNotNull('workout_sets.weight')
            ->whereNotNull('workout_sets.reps')
            ->select([
                'exercises.name as exercise_name',
                'muscle_groups.name as muscle_group',
                DB::raw('COUNT(workout_sets.id) as total_sets'),
                DB::raw('SUM(workout_sets.weight * workout_sets.reps) as total_volume'),
                DB::raw('MAX(workout_sets.weight) as max_weight'),
                DB::raw('AVG(workout_sets.weight) as avg_weight'),
                DB::raw('MAX(workouts.finished_at) as last_performed')
            ])
            ->groupBy('exercises.id', 'exercises.name', 'muscle_groups.name')
            ->orderByDesc('total_sets')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'exercise_name' => $item->exercise_name,
                    'muscle_group' => $item->muscle_group,
                    'total_sets' => (int) $item->total_sets,
                    'total_volume' => (float) $item->total_volume,
                    'max_weight' => (float) $item->max_weight,
                    'avg_weight' => round((float) $item->avg_weight, 2),
                    'last_performed' => $item->last_performed ? \Carbon\Carbon::parse($item->last_performed)->toDateString() : null,
                ];
            })
            ->toArray();
    }

    private function getExerciseProgress(int $userId): array
    {
        $exercises = DB::table('workout_sets')
            ->join('plan_exercises', 'workout_sets.plan_exercise_id', '=', 'plan_exercises.id')
            ->join('exercises', 'plan_exercises.exercise_id', '=', 'exercises.id')
            ->join('muscle_groups', 'exercises.muscle_group_id', '=', 'muscle_groups.id')
            ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $userId)
            ->whereNotNull('workout_sets.weight')
            ->whereNotNull('workout_sets.reps')
            ->where('workouts.finished_at', '>=', now()->subMonths(3))
            ->select([
                'exercises.id as exercise_id',
                'exercises.name as exercise_name',
                'muscle_groups.name as muscle_group',
                DB::raw('DATE(workouts.finished_at) as workout_date'),
                DB::raw('MAX(workout_sets.weight) as max_weight'),
                DB::raw('SUM(workout_sets.weight * workout_sets.reps) as total_volume')
            ])
            ->groupBy('exercises.id', 'exercises.name', 'muscle_groups.name', DB::raw('DATE(workouts.finished_at)'))
            ->orderBy('exercises.name')
            ->orderBy('workout_date')
            ->get();

        $progress = [];
        foreach ($exercises as $exercise) {
            $exerciseName = $exercise->exercise_name;
            if (!isset($progress[$exerciseName])) {
                $progress[$exerciseName] = [
                    'exercise_name' => $exercise->exercise_name,
                    'muscle_group' => $exercise->muscle_group,
                    'weight_progression' => []
                ];
            }
            
            $progress[$exerciseName]['weight_progression'][] = [
                'date' => $exercise->workout_date,
                'max_weight' => (float) $exercise->max_weight,
                'total_volume' => (float) $exercise->total_volume,
            ];
        }

        return array_values($progress);
    }

    private function getWeeklyPattern(int $userId): array
    {
        $daysOfWeek = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        $pattern = [];

        // Получаем все тренировки пользователя
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNotNull('started_at')
            ->get();

        // Группируем по дням недели
        $weeklyData = [];
        foreach ($workouts as $workout) {
            $dayOfWeek = \Carbon\Carbon::parse($workout->finished_at)->dayOfWeek;
            $dayName = $daysOfWeek[$dayOfWeek === 0 ? 6 : $dayOfWeek - 1];
            
            if (!isset($weeklyData[$dayName])) {
                $weeklyData[$dayName] = [
                    'workout_count' => 0,
                    'total_duration' => 0,
                    'total_volume' => 0,
                ];
            }
            
            $weeklyData[$dayName]['workout_count']++;
            $weeklyData[$dayName]['total_duration'] += \Carbon\Carbon::parse($workout->started_at)->diffInMinutes(\Carbon\Carbon::parse($workout->finished_at));
            
            // Получаем объем тренировки
            $volume = DB::table('workout_sets')
                ->where('workout_id', $workout->id)
                ->whereNotNull('weight')
                ->whereNotNull('reps')
                ->sum(DB::raw('weight * reps'));
            
            $weeklyData[$dayName]['total_volume'] += $volume;
        }

        // Формируем результат для всех дней недели
        foreach ($daysOfWeek as $day) {
            $data = $weeklyData[$day] ?? ['workout_count' => 0, 'total_duration' => 0, 'total_volume' => 0];
            $avgDuration = $data['workout_count'] > 0 ? $data['total_duration'] / $data['workout_count'] : 0;
            
            $pattern[] = [
                'day_of_week' => $day,
                'workout_count' => (int) $data['workout_count'],
                'avg_duration' => round($avgDuration, 1),
                'total_volume' => (float) $data['total_volume'],
            ];
        }

        return $pattern;
    }

    private function getMonthlyTrends(int $userId): array
    {
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNotNull('started_at')
            ->where('finished_at', '>=', now()->subYear())
            ->get();

        $monthlyData = [];
        foreach ($workouts as $workout) {
            $month = \Carbon\Carbon::parse($workout->finished_at)->format('Y-m');
            
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'workout_count' => 0,
                    'total_duration' => 0,
                    'total_volume' => 0,
                ];
            }
            
            $monthlyData[$month]['workout_count']++;
            $monthlyData[$month]['total_duration'] += \Carbon\Carbon::parse($workout->started_at)->diffInMinutes(\Carbon\Carbon::parse($workout->finished_at));
            
            // Получаем объем тренировки
            $volume = DB::table('workout_sets')
                ->where('workout_id', $workout->id)
                ->whereNotNull('weight')
                ->whereNotNull('reps')
                ->sum(DB::raw('weight * reps'));
            
            $monthlyData[$month]['total_volume'] += $volume;
        }

        $result = [];
        foreach ($monthlyData as $month => $data) {
            $avgDuration = $data['workout_count'] > 0 ? $data['total_duration'] / $data['workout_count'] : 0;
            
            $result[] = [
                'month' => $month,
                'workout_count' => (int) $data['workout_count'],
                'total_volume' => (float) $data['total_volume'],
                'avg_duration' => round($avgDuration, 1),
            ];
        }

        usort($result, fn($a, $b) => $a['month'] <=> $b['month']);
        return $result;
    }

    private function getVolumeTrends(int $userId): array
    {
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->where('finished_at', '>=', now()->subWeeks(12))
            ->get();

        $weeklyData = [];
        foreach ($workouts as $workout) {
            $week = \Carbon\Carbon::parse($workout->finished_at)->format('Y-W');
            
            if (!isset($weeklyData[$week])) {
                $weeklyData[$week] = [
                    'workout_count' => 0,
                    'total_volume' => 0,
                ];
            }
            
            $weeklyData[$week]['workout_count']++;
            
            // Получаем объем тренировки
            $volume = DB::table('workout_sets')
                ->where('workout_id', $workout->id)
                ->whereNotNull('weight')
                ->whereNotNull('reps')
                ->sum(DB::raw('weight * reps'));
            
            $weeklyData[$week]['total_volume'] += $volume;
        }

        $result = [];
        foreach ($weeklyData as $week => $data) {
            $result[] = [
                'week' => $week,
                'total_volume' => (float) $data['total_volume'],
                'workout_count' => (int) $data['workout_count'],
            ];
        }

        usort($result, fn($a, $b) => $a['week'] <=> $b['week']);
        return $result;
    }

    private function getMuscleGroupStats(int $userId): array
    {
        return DB::table('workout_sets')
            ->join('plan_exercises', 'workout_sets.plan_exercise_id', '=', 'plan_exercises.id')
            ->join('exercises', 'plan_exercises.exercise_id', '=', 'exercises.id')
            ->join('muscle_groups', 'exercises.muscle_group_id', '=', 'muscle_groups.id')
            ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $userId)
            ->whereNotNull('workout_sets.weight')
            ->whereNotNull('workout_sets.reps')
            ->select([
                'muscle_groups.name as muscle_group_name',
                'muscle_groups.size_factor',
                'muscle_groups.optimal_frequency_per_week',
                DB::raw('SUM(workout_sets.weight * workout_sets.reps) as total_volume'),
                DB::raw('COUNT(DISTINCT workouts.id) as workout_count'),
                DB::raw('COUNT(DISTINCT exercises.id) as exercise_count'),
                DB::raw('MAX(workouts.finished_at) as last_trained'),
                DB::raw('MIN(workouts.finished_at) as first_trained'),
                DB::raw('COUNT(DISTINCT DATE(workouts.finished_at)) as unique_training_days')
            ])
            ->groupBy('muscle_groups.id', 'muscle_groups.name')
            ->get()
            ->map(function ($item) {
                $avgVolumePerWorkout = $item->workout_count > 0 ? $item->total_volume / $item->workout_count : 0;
                
                // Рассчитываем дни с последней тренировки в PHP
                $daysSinceLastTraining = 0;
                if ($item->last_trained) {
                    $lastTrainedDate = \Carbon\Carbon::parse($item->last_trained);
                    $daysSinceLastTraining = $lastTrainedDate->diffInDays(now());
                }
                
                return [
                    'muscle_group_name' => $item->muscle_group_name,
                    'size_factor' => (float) $item->size_factor,
                    'optimal_frequency_per_week' => (int) $item->optimal_frequency_per_week,
                    'total_volume' => (float) $item->total_volume,
                    'workout_count' => (int) $item->workout_count,
                    'exercise_count' => (int) $item->exercise_count,
                    'avg_volume_per_workout' => round($avgVolumePerWorkout, 2),
                    'last_trained' => $item->last_trained ? \Carbon\Carbon::parse($item->last_trained)->toDateString() : null,
                    'first_trained' => $item->first_trained ? \Carbon\Carbon::parse($item->first_trained)->toDateString() : null,
                    'unique_training_days' => (int) $item->unique_training_days,
                    'days_since_last_training' => $daysSinceLastTraining,
                ];
            })
            ->toArray();
    }

    private function getBalanceAnalysis(array $muscleGroupStats): array
    {
        if (empty($muscleGroupStats)) {
            return [
                'most_trained' => null,
                'least_trained' => null,
                'balance_score' => 0,
                'recommendations' => []
            ];
        }

        // Рассчитываем временные факторы и нормализованные объемы
        $enhancedStats = $this->calculateTemporalFactors($muscleGroupStats);
        
        // Сортируем расширенную статистику по combined_balance_factor
        // Это более надежный способ найти мин/макс элементы, особенно с float
        usort($enhancedStats, function ($a, $b) {
            return $a['combined_balance_factor'] <=> $b['combined_balance_factor'];
        });

        $leastTrained = reset($enhancedStats); // Первый элемент после сортировки по возрастанию
        $mostTrained = end($enhancedStats);   // Последний элемент после сортировки по возрастанию

        $minFactor = $leastTrained['combined_balance_factor'];
        $maxFactor = $mostTrained['combined_balance_factor'];
        
        // Улучшенный коэффициент баланса
        $balanceScore = $minFactor > 0 ? round($minFactor / $maxFactor, 2) : 0;
        
        // Генерация рекомендаций на основе улучшенного анализа
        $recommendations = $this->generateAdvancedRecommendations($balanceScore, $enhancedStats, $leastTrained);

        return [
            'most_trained' => $mostTrained['muscle_group_name'],
            'least_trained' => $leastTrained['muscle_group_name'],
            'balance_score' => $balanceScore,
            'recommendations' => $recommendations
        ];
    }

    /**
     * Рассчитывает временные факторы для каждой группы мышц
     */
    private function calculateTemporalFactors(array $stats): array
    {
        foreach ($stats as &$stat) {
            // Частота тренировок в неделю
            $weeksSinceFirst = max(1, \Carbon\Carbon::parse($stat['first_trained'])->diffInWeeks(now()));
            $stat['frequency_per_week'] = $stat['unique_training_days'] / $weeksSinceFirst;
            
            // Отношение к оптимальной частоте
            $stat['frequency_ratio'] = $stat['frequency_per_week'] / $stat['optimal_frequency_per_week'];
            
            // Актуальность (чем дольше не тренировались, тем хуже)
            $stat['recency_factor'] = max(0.1, 1 - ($stat['days_since_last_training'] / 14));
            
            // Нормализованный объем с учетом размера группы
            $stat['normalized_volume'] = $stat['total_volume'] / $stat['size_factor'];
            
            // Временной фактор (частота + актуальность)
            $stat['temporal_factor'] = ($stat['frequency_ratio'] + $stat['recency_factor']) / 2;
            
            // Комбинированный показатель баланса
            $stat['combined_balance_factor'] = $stat['normalized_volume'] * $stat['temporal_factor'];
        }
        
        return $stats;
    }

    /**
     * Генерирует улучшенные рекомендации на основе комплексного анализа
     */
    private function generateAdvancedRecommendations(float $balanceScore, array $stats, array $leastTrained): array
    {
        $recommendations = [];
        
        if ($balanceScore < 0.3) {
            $recommendations[] = "Рекомендуется увеличить нагрузку на группу мышц: {$leastTrained['muscle_group_name']}";
            
            // Дополнительные рекомендации на основе временных факторов
            if ($leastTrained['days_since_last_training'] > 7) {
                $recommendations[] = "Группа мышц '{$leastTrained['muscle_group_name']}' не тренировалась более недели";
            }
            
            if ($leastTrained['frequency_ratio'] < 0.5) {
                $recommendations[] = "Частота тренировок группы '{$leastTrained['muscle_group_name']}' ниже оптимальной";
            }
        } elseif ($balanceScore > 0.7) {
            $recommendations[] = "Отличный баланс между мышечными группами!";
        } else {
            $recommendations[] = "Баланс тренировок удовлетворительный, есть возможности для улучшения";
        }
        
        return $recommendations;
    }

    private function getPersonalRecords(int $userId): array
    {
        return DB::table('workout_sets')
            ->join('plan_exercises', 'workout_sets.plan_exercise_id', '=', 'plan_exercises.id')
            ->join('exercises', 'plan_exercises.exercise_id', '=', 'exercises.id')
            ->join('muscle_groups', 'exercises.muscle_group_id', '=', 'muscle_groups.id')
            ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $userId)
            ->whereNotNull('workout_sets.weight')
            ->whereNotNull('workout_sets.reps')
            ->select([
                'exercises.name as exercise_name',
                'muscle_groups.name as muscle_group',
                DB::raw('MAX(workout_sets.weight) as max_weight'),
                DB::raw('MAX(workout_sets.reps) as max_reps'),
                DB::raw('MAX(workout_sets.weight * workout_sets.reps) as max_volume'),
                DB::raw('MAX(workouts.finished_at) as achieved_date')
            ])
            ->groupBy('exercises.id', 'exercises.name', 'muscle_groups.name')
            ->orderByDesc('max_weight')
            ->get()
            ->map(function ($item) {
                return [
                    'exercise_name' => $item->exercise_name,
                    'muscle_group' => $item->muscle_group,
                    'max_weight' => (float) $item->max_weight,
                    'max_reps' => (int) $item->max_reps,
                    'max_volume' => (float) $item->max_volume,
                    'achieved_date' => $item->achieved_date ? \Carbon\Carbon::parse($item->achieved_date)->toDateString() : null,
                ];
            })
            ->toArray();
    }

    private function getWorkoutRecords(int $userId): array
    {
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNotNull('started_at')
            ->get();

        $workoutData = [];
        foreach ($workouts as $workout) {
            $durationMinutes = \Carbon\Carbon::parse($workout->started_at)->diffInMinutes(\Carbon\Carbon::parse($workout->finished_at));
            
            $totalVolume = DB::table('workout_sets')
                ->where('workout_id', $workout->id)
                ->whereNotNull('weight')
                ->whereNotNull('reps')
                ->sum(DB::raw('weight * reps'));
            
            $exerciseCount = DB::table('workout_sets')
                ->where('workout_id', $workout->id)
                ->distinct('plan_exercise_id')
                ->count('plan_exercise_id');

            $workoutData[] = [
                'id' => $workout->id,
                'finished_at' => $workout->finished_at,
                'duration_minutes' => $durationMinutes,
                'total_volume' => (float) $totalVolume,
                'exercise_count' => (int) $exerciseCount,
            ];
        }

        $maxVolumeWorkout = collect($workoutData)->sortByDesc('total_volume')->first();
        $longestWorkout = collect($workoutData)->sortByDesc('duration_minutes')->first();
        $mostExercisesWorkout = collect($workoutData)->sortByDesc('exercise_count')->first();

        return [
            'max_volume_workout' => $maxVolumeWorkout ? [
                'date' => \Carbon\Carbon::parse($maxVolumeWorkout['finished_at'])->toDateString(),
                'total_volume' => $maxVolumeWorkout['total_volume'],
                'duration_minutes' => $maxVolumeWorkout['duration_minutes'],
            ] : null,
            'longest_workout' => $longestWorkout ? [
                'date' => \Carbon\Carbon::parse($longestWorkout['finished_at'])->toDateString(),
                'duration_minutes' => $longestWorkout['duration_minutes'],
                'total_volume' => $longestWorkout['total_volume'],
            ] : null,
            'most_exercises_workout' => $mostExercisesWorkout ? [
                'date' => \Carbon\Carbon::parse($mostExercisesWorkout['finished_at'])->toDateString(),
                'exercise_count' => $mostExercisesWorkout['exercise_count'],
                'total_volume' => $mostExercisesWorkout['total_volume'],
            ] : null,
        ];
    }
}
