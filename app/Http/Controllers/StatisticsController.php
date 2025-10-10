<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
     *                 @OA\Property(property="total_volume", type="integer", example=125000),
     *                 @OA\Property(property="current_weight", type="number", format="float", example=75.5),
     *                 @OA\Property(property="active_cycles_count", type="integer", example=2),
     *                 @OA\Property(property="weight_change_30_days", type="number", format="float", example=2.5),
     *                 @OA\Property(property="training_frequency_4_weeks", type="number", format="float", example=3.2),
     *                 @OA\Property(property="training_streak_days", type="integer", example=7)
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

        $avgWorkoutsPerWeek = count($weeks) > 0 ? array_sum($weeks) / count($weeks) : 0;
        return round($avgWorkoutsPerWeek, 1);
    }

    /**
     * Get training streak (consecutive days with completed workouts).
     */
    private function getTrainingStreak(int $userId): int
    {
        $workoutDates = User::find($userId)
            ->workouts()
            ->whereNotNull('finished_at')
            ->selectRaw('DATE(finished_at) as workout_date')
            ->distinct()
            ->orderBy('workout_date', 'desc')
            ->pluck('workout_date')
            ->map(fn($date) => \Carbon\Carbon::parse($date)->toDateString())
            ->toArray();

        $streak = 0;
        $currentDate = now()->toDateString();

        foreach ($workoutDates as $workoutDate) {
            if ($workoutDate === $currentDate || $workoutDate === now()->subDays($streak)->toDateString()) {
                $streak++;
            } else {
                break;
            }
        }

        return $streak;
    }
}
