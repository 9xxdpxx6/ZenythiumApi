<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Cycle;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class StatisticsController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/v1/user/statistics",
     *     summary="Получение статистики пользователя",
     *     description="Возвращает комплексную статистику тренировок и прогресса пользователя. Включает общее количество тренировок, среднее время тренировки за 30 дней, объемы, изменения веса и частоту тренировок",
     *     tags={"Statistics"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Статистика пользователя успешно получена",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_workouts", type="integer", example=45),
     *                 @OA\Property(property="completed_workouts", type="integer", example=42),
     *                 @OA\Property(property="avg_training_time_30_days", type="integer", example=65, description="Среднее время тренировки в минутах за последние 30 дней"),
     *                 @OA\Property(property="total_volume", type="number", format="float", example=125000.00, description="Суммарный объём (вес × повторения) по всем завершённым тренировкам"),
     *                 @OA\Property(property="current_weight", type="number", format="float", example=75.5),
     *                 @OA\Property(property="active_cycles_count", type="integer", example=1, description="Количество активных циклов (без end_date)"),
     *                 @OA\Property(property="weight_change_30_days", type="number", example=2, description="Изменение веса за 30 дней (разница между последним замером и замером ~30 дней назад). null если недостаточно данных"),
     *                 @OA\Property(property="training_frequency_4_weeks", type="number", example=3.5, description="Среднее количество тренировок в неделю за последние 35 дней. Делится на фактическое число недель активности (макс. 5)"),
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

        try {
            $user = User::findOrFail($userId);

            // Basic workout statistics
            $totalWorkouts = $user->workouts()->count();
            $completedWorkouts = $user->workouts()->whereNotNull('finished_at')->count();
            
            // Average training time in minutes (last 30 days)
            try {
                $recentWorkouts = $user->workouts()
                    ->whereNotNull('started_at')
                    ->whereNotNull('finished_at')
                    ->where('finished_at', '>=', now()->subDays(30))
                    ->get();
                
                $avgTrainingTime = $recentWorkouts->count() > 0
                    ? $recentWorkouts->avg(fn ($workout) => $workout->duration_minutes ?? 0)
                    : 0;
            } catch (Throwable $e) {
                Log::error('StatisticsController::statistics - Error calculating avg training time', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $avgTrainingTime = 0;
            }

            // Total volume (weight × reps) — only from completed workouts
            try {
                $totalVolume = DB::table('workout_sets')
                    ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
                    ->where('workouts.user_id', $userId)
                    ->whereNotNull('workouts.finished_at')
                    ->whereNotNull('workout_sets.weight')
                    ->whereNotNull('workout_sets.reps')
                    ->sum(DB::raw('workout_sets.weight * workout_sets.reps'));
            } catch (Throwable $e) {
                Log::error('StatisticsController::statistics - Error calculating total volume', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $totalVolume = 0;
            }

            // Current weight from latest metric
            try {
                $currentWeight = $user->current_weight;
            } catch (Throwable $e) {
                Log::error('StatisticsController::statistics - Error getting current weight', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $currentWeight = null;
            }

            // Active cycles count (only truly active — no end_date)
            try {
                $activeCyclesCount = $user->cycles()
                    ->whereNull('end_date')
                    ->count();
            } catch (Throwable $e) {
                Log::error('StatisticsController::statistics - Error calculating active cycles', [
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
                $activeCyclesCount = 0;
            }

            // Weight change over time (last 30 days)
            $weightChange = $this->getWeightChange($userId);

            // Training frequency (workouts per week in last 35 days / 5 weeks)
            $trainingFrequency = $this->getTrainingFrequency($userId);

            return response()->json([
                'data' => [
                    'total_workouts' => (int) $totalWorkouts,
                    'completed_workouts' => (int) $completedWorkouts,
                    'avg_training_time_30_days' => (int) round($avgTrainingTime ?? 0),
                    'total_volume' => (float) ($totalVolume ?? 0),
                    'current_weight' => $currentWeight !== null ? (float) $currentWeight : null,
                    'active_cycles_count' => (int) $activeCyclesCount,
                    'weight_change_30_days' => $weightChange !== null ? (float) $weightChange : null,
                    'training_frequency_4_weeks' => (float) ($trainingFrequency ?? 0),
                ],
                'message' => 'Статистика пользователя успешно получена'
            ]);
        } catch (ModelNotFoundException $e) {
            Log::error('StatisticsController::statistics - User not found', [
                'user_id' => $userId,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Пользователь не найден'
            ], 404);
        } catch (QueryException $e) {
            Log::error('StatisticsController::statistics - Database query error', [
                'user_id' => $userId,
                'exception' => $e->getMessage(),
                'sql' => $e->getSql() ?? null,
                'bindings' => $e->getBindings() ?? null,
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Ошибка при получении статистики. Попробуйте позже.'
            ], 500);
        } catch (Throwable $e) {
            Log::error('StatisticsController::statistics - Unexpected error', [
                'user_id' => $userId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Произошла ошибка при получении статистики. Попробуйте позже.'
            ], 500);
        }
    }

    /**
     * Get weight change over last 30 days.
     * 
     * Uses the last metric recorded BEFORE the 30-day window as baseline,
     * compared to the most recent metric. Returns null if insufficient data.
     */
    private function getWeightChange(int $userId): ?float
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                Log::warning('StatisticsController::getWeightChange - User not found', [
                    'user_id' => $userId,
                ]);
                return null;
            }

            $thirtyDaysAgo = now()->subDays(30);

            // Last metric within the 30-day window (most recent)
            $latestMetric = $user->metrics()
                ->orderByDesc('date')
                ->first();

            if (!$latestMetric) {
                return null;
            }

            // Baseline: last metric recorded before or at the start of the 30-day window
            $baselineMetric = $user->metrics()
                ->where('date', '<=', $thirtyDaysAgo)
                ->orderByDesc('date')
                ->first();

            // Fallback: if no metric before the window, use the earliest metric within the window
            if (!$baselineMetric) {
                $baselineMetric = $user->metrics()
                    ->where('date', '>=', $thirtyDaysAgo)
                    ->orderBy('date')
                    ->first();
            }

            if (!$baselineMetric || $baselineMetric->id === $latestMetric->id) {
                return null;
            }

            return (float) $latestMetric->weight - (float) $baselineMetric->weight;
        } catch (Throwable $e) {
            Log::error('StatisticsController::getWeightChange - Error', [
                'user_id' => $userId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Get training frequency (workouts per week) over last 35 days (5 weeks).
     * 
     * Divides by actual weeks of training activity (capped at 5) to avoid
     * misleading results for new users with few workouts.
     */
    private function getTrainingFrequency(int $userId): float
    {
        try {
            $startDate = \Carbon\Carbon::today()->subDays(35)->startOfDay();
            
            $user = User::find($userId);
            if (!$user) {
                Log::warning('StatisticsController::getTrainingFrequency - User not found', [
                    'user_id' => $userId,
                ]);
                return 0.0;
            }

            // Count completed workouts in the last 35 days
            $totalWorkouts = $user->workouts()
                ->whereNotNull('finished_at')
                ->whereDate('finished_at', '>=', $startDate)
                ->count();

            if ($totalWorkouts === 0) {
                return 0.0;
            }

            // Find earliest completed workout to determine actual active period
            $firstWorkoutDate = $user->workouts()
                ->whereNotNull('finished_at')
                ->orderBy('finished_at')
                ->value('finished_at');

            if (!$firstWorkoutDate) {
                return 0.0;
            }

            // Actual weeks since first workout (minimum 1, maximum 5)
            $firstWorkout = \Carbon\Carbon::parse($firstWorkoutDate);
            $actualWeeks = max(1, min(5, ceil($firstWorkout->diffInDays(now()) / 7)));

            $avgWorkoutsPerWeek = $totalWorkouts / $actualWeeks;
            return round($avgWorkoutsPerWeek, 1);
        } catch (Throwable $e) {
            Log::error('StatisticsController::getTrainingFrequency - Error', [
                'user_id' => $userId,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 0.0;
        }
    }

    /**
     * @OA\Get(
     *     path="/api/v1/user/exercise-statistics",
     *     summary="Получение детальной статистики по упражнениям",
     *     description="Возвращает детальную статистику по упражнениям пользователя: топ-10 упражнений по количеству подходов (только завершённые тренировки), прогресс по весам за последние 3 месяца",
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
     *     description="Возвращает статистику по мышечным группам (только завершённые тренировки): объем работы, частота тренировок, анализ баланса",
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
     *     description="Возвращает личные рекорды пользователя (только завершённые тренировки): максимальные веса с датой первого достижения, рекорды по объёму и длительности тренировок",
     *     tags={"Statistics"},
     *     security={{"sanctum": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Рекорды пользователя успешно получены",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="personal_records", type="array", description="Рекорды по упражнениям. max_weight и max_reps гарантированно из одного подхода (самый тяжёлый сет)", @OA\Items(
     *                     @OA\Property(property="exercise_name", type="string"),
     *                     @OA\Property(property="muscle_group", type="string"),
     *                     @OA\Property(property="max_weight", type="number", description="Максимальный вес в одном подходе"),
     *                     @OA\Property(property="max_reps", type="integer", description="Количество повторений в подходе с максимальным весом"),
     *                     @OA\Property(property="max_volume", type="number", description="Максимальный объём (вес × повторения) за один подход (может быть из другого сета)"),
     *                     @OA\Property(property="achieved_date", type="string", format="date", description="Дата тренировки, где был установлен рекорд по весу")
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
            ->whereNotNull('workouts.finished_at')
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
            ->limit(10)
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
            ->whereNotNull('workouts.finished_at')
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

        // Query 1: workouts with duration info
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNotNull('started_at')
            ->select(['id', 'started_at', 'finished_at'])
            ->get();

        if ($workouts->isEmpty()) {
            return collect($daysOfWeek)->map(fn ($day) => [
                'day_of_week' => $day,
                'workout_count' => 0,
                'avg_duration' => 0,
                'total_volume' => 0,
            ])->toArray();
        }

        // Query 2: volume per workout in a single query (eliminates N+1)
        $volumes = DB::table('workout_sets')
            ->whereIn('workout_id', $workouts->pluck('id'))
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->select(['workout_id', DB::raw('SUM(weight * reps) as total_volume')])
            ->groupBy('workout_id')
            ->pluck('total_volume', 'workout_id');

        // Group by day of week in PHP
        $weeklyData = [];
        foreach ($workouts as $workout) {
            $dayOfWeek = \Carbon\Carbon::parse($workout->finished_at)->dayOfWeek;
            $dayName = $daysOfWeek[$dayOfWeek === 0 ? 6 : $dayOfWeek - 1];
            
            if (!isset($weeklyData[$dayName])) {
                $weeklyData[$dayName] = ['workout_count' => 0, 'total_duration' => 0, 'total_volume' => 0];
            }
            
            $weeklyData[$dayName]['workout_count']++;
            $weeklyData[$dayName]['total_duration'] += \Carbon\Carbon::parse($workout->started_at)
                ->diffInMinutes(\Carbon\Carbon::parse($workout->finished_at));
            $weeklyData[$dayName]['total_volume'] += (float) ($volumes[$workout->id] ?? 0);
        }

        return collect($daysOfWeek)->map(function ($day) use ($weeklyData) {
            $data = $weeklyData[$day] ?? ['workout_count' => 0, 'total_duration' => 0, 'total_volume' => 0];
            $avgDuration = $data['workout_count'] > 0 ? $data['total_duration'] / $data['workout_count'] : 0;
            
            return [
                'day_of_week' => $day,
                'workout_count' => (int) $data['workout_count'],
                'avg_duration' => round($avgDuration, 1),
                'total_volume' => (float) $data['total_volume'],
            ];
        })->toArray();
    }

    private function getMonthlyTrends(int $userId): array
    {
        // Query 1: workouts for the last year
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNotNull('started_at')
            ->where('finished_at', '>=', now()->subYear())
            ->select(['id', 'started_at', 'finished_at'])
            ->get();

        if ($workouts->isEmpty()) {
            return [];
        }

        // Query 2: volume per workout in a single query (eliminates N+1)
        $volumes = DB::table('workout_sets')
            ->whereIn('workout_id', $workouts->pluck('id'))
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->select(['workout_id', DB::raw('SUM(weight * reps) as total_volume')])
            ->groupBy('workout_id')
            ->pluck('total_volume', 'workout_id');

        // Group by month in PHP
        $monthlyData = [];
        foreach ($workouts as $workout) {
            $month = \Carbon\Carbon::parse($workout->finished_at)->format('Y-m');
            
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = ['workout_count' => 0, 'total_duration' => 0, 'total_volume' => 0];
            }
            
            $monthlyData[$month]['workout_count']++;
            $monthlyData[$month]['total_duration'] += \Carbon\Carbon::parse($workout->started_at)
                ->diffInMinutes(\Carbon\Carbon::parse($workout->finished_at));
            $monthlyData[$month]['total_volume'] += (float) ($volumes[$workout->id] ?? 0);
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
        // Query 1: workouts for last 12 weeks
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->where('finished_at', '>=', now()->subWeeks(12))
            ->select(['id', 'finished_at'])
            ->get();

        if ($workouts->isEmpty()) {
            return [];
        }

        // Query 2: volume per workout in a single query (eliminates N+1)
        $volumes = DB::table('workout_sets')
            ->whereIn('workout_id', $workouts->pluck('id'))
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->select(['workout_id', DB::raw('SUM(weight * reps) as total_volume')])
            ->groupBy('workout_id')
            ->pluck('total_volume', 'workout_id');

        // Group by week in PHP
        $weeklyData = [];
        foreach ($workouts as $workout) {
            $week = \Carbon\Carbon::parse($workout->finished_at)->format('Y-W');
            
            if (!isset($weeklyData[$week])) {
                $weeklyData[$week] = ['workout_count' => 0, 'total_volume' => 0];
            }
            
            $weeklyData[$week]['workout_count']++;
            $weeklyData[$week]['total_volume'] += (float) ($volumes[$workout->id] ?? 0);
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
            ->whereNotNull('workouts.finished_at')
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

    /**
     * Рекорды по упражнениям.
     *
     * Для каждого упражнения берётся КОНКРЕТНЫЙ подход с максимальным весом
     * (при одинаковом весе — больше повторений). max_weight и max_reps гарантированно
     * из одного подхода. max_volume — лучший объём за один подход (может быть другой сет).
     * achieved_date — дата тренировки, где был установлен рекорд по весу.
     */
    private function getPersonalRecords(int $userId): array
    {
        // Single query: ROW_NUMBER() picks the actual best-weight set per exercise.
        // MAX() OVER gives the true best single-set volume independently.
        $records = DB::select("
            SELECT exercise_name, muscle_group, max_weight, max_reps, max_volume, achieved_date
            FROM (
                SELECT
                    e.name AS exercise_name,
                    mg.name AS muscle_group,
                    ws.weight AS max_weight,
                    ws.reps AS max_reps,
                    MAX(ws.weight * ws.reps) OVER (PARTITION BY e.id) AS max_volume,
                    w.finished_at AS achieved_date,
                    ROW_NUMBER() OVER (
                        PARTITION BY e.id
                        ORDER BY ws.weight DESC, ws.reps DESC
                    ) AS rn
                FROM workout_sets ws
                INNER JOIN plan_exercises pe ON ws.plan_exercise_id = pe.id
                INNER JOIN exercises e ON pe.exercise_id = e.id
                INNER JOIN muscle_groups mg ON e.muscle_group_id = mg.id
                INNER JOIN workouts w ON ws.workout_id = w.id
                WHERE w.user_id = ?
                  AND w.finished_at IS NOT NULL
                  AND ws.weight IS NOT NULL
                  AND ws.reps IS NOT NULL
            ) ranked
            WHERE rn = 1
            ORDER BY max_weight DESC
        ", [$userId]);

        return collect($records)->map(fn ($item) => [
            'exercise_name' => $item->exercise_name,
            'muscle_group' => $item->muscle_group,
            'max_weight' => (float) $item->max_weight,
            'max_reps' => (int) $item->max_reps,
            'max_volume' => (float) $item->max_volume,
            'achieved_date' => $item->achieved_date
                ? \Carbon\Carbon::parse($item->achieved_date)->toDateString()
                : null,
        ])->toArray();
    }

    private function getWorkoutRecords(int $userId): array
    {
        // Query 1: all completed workouts
        $workouts = DB::table('workouts')
            ->where('user_id', $userId)
            ->whereNotNull('finished_at')
            ->whereNotNull('started_at')
            ->select(['id', 'started_at', 'finished_at'])
            ->get();

        if ($workouts->isEmpty()) {
            return [
                'max_volume_workout' => null,
                'longest_workout' => null,
                'most_exercises_workout' => null,
            ];
        }

        // Query 2: volume + exercise count per workout in a single query (eliminates N+1)
        $setStats = DB::table('workout_sets')
            ->whereIn('workout_id', $workouts->pluck('id'))
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->select([
                'workout_id',
                DB::raw('SUM(weight * reps) as total_volume'),
                DB::raw('COUNT(DISTINCT plan_exercise_id) as exercise_count'),
            ])
            ->groupBy('workout_id')
            ->get()
            ->keyBy('workout_id');

        // Build records in PHP
        $workoutData = $workouts->map(function ($workout) use ($setStats) {
            $stats = $setStats[$workout->id] ?? null;
            return [
                'finished_at' => $workout->finished_at,
                'duration_minutes' => \Carbon\Carbon::parse($workout->started_at)
                    ->diffInMinutes(\Carbon\Carbon::parse($workout->finished_at)),
                'total_volume' => (float) ($stats->total_volume ?? 0),
                'exercise_count' => (int) ($stats->exercise_count ?? 0),
            ];
        });

        $maxVolumeWorkout = $workoutData->sortByDesc('total_volume')->first();
        $longestWorkout = $workoutData->sortByDesc('duration_minutes')->first();
        $mostExercisesWorkout = $workoutData->sortByDesc('exercise_count')->first();

        return [
            'max_volume_workout' => $maxVolumeWorkout && $maxVolumeWorkout['total_volume'] > 0 ? [
                'date' => \Carbon\Carbon::parse($maxVolumeWorkout['finished_at'])->toDateString(),
                'total_volume' => $maxVolumeWorkout['total_volume'],
                'duration_minutes' => $maxVolumeWorkout['duration_minutes'],
            ] : null,
            'longest_workout' => $longestWorkout && $longestWorkout['duration_minutes'] > 0 ? [
                'date' => \Carbon\Carbon::parse($longestWorkout['finished_at'])->toDateString(),
                'duration_minutes' => $longestWorkout['duration_minutes'],
                'total_volume' => $longestWorkout['total_volume'],
            ] : null,
            'most_exercises_workout' => $mostExercisesWorkout && $mostExercisesWorkout['exercise_count'] > 0 ? [
                'date' => \Carbon\Carbon::parse($mostExercisesWorkout['finished_at'])->toDateString(),
                'exercise_count' => $mostExercisesWorkout['exercise_count'],
                'total_volume' => $mostExercisesWorkout['total_volume'],
            ] : null,
        ];
    }
}
