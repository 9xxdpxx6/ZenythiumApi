<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use App\Models\Cycle;
use App\Models\Goal;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Notifications\GoalAchievedNotification;
use App\Notifications\GoalProgressNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class GoalService
{
    /**
     * Рассчитать текущее значение для цели
     *
     * @param Goal $goal Цель
     * @return float Текущее значение
     */
    public function calculateCurrentValue(Goal $goal): float
    {
        $user = $goal->user;
        $startDate = $goal->start_date;
        $endDate = $goal->end_date ?? now();

        return match ($goal->type) {
            GoalType::TOTAL_WORKOUTS => $this->calculateTotalWorkouts($user, $startDate, $endDate),
            GoalType::COMPLETED_WORKOUTS => $this->calculateCompletedWorkouts($user, $startDate, $endDate),
            GoalType::TARGET_WEIGHT => $this->calculateTargetWeight($user),
            GoalType::WEIGHT_LOSS => $this->calculateWeightLoss($user, $startDate, $endDate),
            GoalType::WEIGHT_GAIN => $this->calculateWeightGain($user, $startDate, $endDate),
            GoalType::TOTAL_VOLUME => $this->calculateTotalVolume($user, $startDate, $endDate),
            GoalType::WEEKLY_VOLUME => $this->calculateWeeklyVolume($user, $startDate, $endDate),
            GoalType::TOTAL_TRAINING_TIME => $this->calculateTotalTrainingTime($user, $startDate, $endDate),
            GoalType::WEEKLY_TRAINING_TIME => $this->calculateWeeklyTrainingTime($user, $startDate, $endDate),
            GoalType::TRAINING_FREQUENCY => $this->calculateTrainingFrequency($user),
            GoalType::EXERCISE_MAX_WEIGHT => $this->calculateExerciseMaxWeight($user, $goal->exercise_id),
            GoalType::EXERCISE_MAX_REPS => $this->calculateExerciseMaxReps($user, $goal->exercise_id),
            GoalType::EXERCISE_VOLUME => $this->calculateExerciseVolume($user, $goal->exercise_id, $startDate, $endDate),
        };
    }

    /**
     * Обновить прогресс цели
     *
     * @param Goal $goal Цель
     * @return void
     */
    public function updateProgress(Goal $goal): void
    {
        if (!$goal->isActive()) {
            return;
        }

        $currentValue = $this->calculateCurrentValue($goal);
        $progressPercentage = $goal->target_value > 0
            ? min(100, (int) round(($currentValue / $goal->target_value) * 100))
            : 0;

        $goal->current_value = $currentValue;
        $goal->progress_percentage = $progressPercentage;
        $goal->save();

        // Проверяем достижение цели
        if ($currentValue >= $goal->target_value && !$goal->isCompleted()) {
            $this->markAsCompleted($goal, $currentValue);
            $this->checkAndNotifyAchievement($goal);
        } else {
            // Проверяем вехи прогресса
            $this->checkAndNotifyProgress($goal);
        }
    }

    /**
     * Обновить прогресс всех активных целей пользователя
     *
     * @param int $userId ID пользователя
     * @return void
     */
    public function updateProgressForUser(int $userId): void
    {
        $goals = Goal::where('user_id', $userId)
            ->where('status', GoalStatus::ACTIVE)
            ->get();

        foreach ($goals as $goal) {
            try {
                $this->updateProgress($goal);
            } catch (\Exception $e) {
                Log::error('Failed to update goal progress', [
                    'goal_id' => $goal->id,
                    'user_id' => $userId,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Проверить и отправить уведомление о достижении цели
     *
     * @param Goal $goal Цель
     * @return void
     */
    public function checkAndNotifyAchievement(Goal $goal): void
    {
        $preferences = $this->getUserNotificationPreferences($goal->user_id);
        
        if (!$preferences || !$preferences->goal_achieved_enabled) {
            return;
        }

        // Проверяем, не было ли уже отправлено уведомление
        $alreadySent = \App\Models\GoalNotification::where('goal_id', $goal->id)
            ->where('notification_type', 'achieved')
            ->exists();

        if ($alreadySent) {
            return;
        }

        $goal->user->notify(new GoalAchievedNotification($goal));

        // Сохраняем в историю
        \App\Models\GoalNotification::create([
            'goal_id' => $goal->id,
            'notification_type' => 'achieved',
            'milestone' => null,
            'sent_at' => now(),
        ]);
    }

    /**
     * Проверить и отправить уведомление о прогрессе (вехи)
     *
     * @param Goal $goal Цель
     * @return void
     */
    public function checkAndNotifyProgress(Goal $goal): void
    {
        $preferences = $this->getUserNotificationPreferences($goal->user_id);
        
        if (!$preferences || !$preferences->goal_progress_enabled) {
            return;
        }

        $milestones = $preferences->goal_progress_milestones ?? [25, 50, 75, 90];
        $currentMilestone = $this->getCurrentMilestone($goal->progress_percentage, $milestones);

        if ($currentMilestone === null) {
            return;
        }

        // Проверяем, не была ли уже отправлена эта веха
        if ($goal->last_notified_milestone !== null && $goal->last_notified_milestone >= $currentMilestone) {
            return;
        }

        $alreadySent = \App\Models\GoalNotification::where('goal_id', $goal->id)
            ->where('notification_type', 'progress')
            ->where('milestone', $currentMilestone)
            ->exists();

        if ($alreadySent) {
            return;
        }

        $goal->user->notify(new GoalProgressNotification($goal, $currentMilestone));

        // Обновляем последнюю отправленную веху
        $goal->last_notified_milestone = $currentMilestone;
        $goal->save();

        // Сохраняем в историю
        \App\Models\GoalNotification::create([
            'goal_id' => $goal->id,
            'notification_type' => 'progress',
            'milestone' => $currentMilestone,
            'sent_at' => now(),
        ]);
    }

    /**
     * Пометить цель как достигнутую
     *
     * @param Goal $goal Цель
     * @param float $achievedValue Достигнутое значение
     * @return void
     */
    public function markAsCompleted(Goal $goal, float $achievedValue): void
    {
        $goal->status = GoalStatus::COMPLETED;
        $goal->completed_at = now();
        $goal->achieved_value = $achievedValue;
        $goal->progress_percentage = 100;
        $goal->save();
    }

    /**
     * Пометить цель как проваленную
     *
     * @param Goal $goal Цель
     * @return void
     */
    public function markAsFailed(Goal $goal): void
    {
        $goal->status = GoalStatus::FAILED;
        $goal->save();

        $preferences = $this->getUserNotificationPreferences($goal->user_id);
        
        if ($preferences && $preferences->goal_failed_enabled) {
            $goal->user->notify(new \App\Notifications\GoalFailedNotification($goal));

            // Сохраняем в историю
            \App\Models\GoalNotification::create([
                'goal_id' => $goal->id,
                'notification_type' => 'failed',
                'milestone' => null,
                'sent_at' => now(),
            ]);
        }
    }

    /**
     * Получить статистику достижений пользователя
     *
     * @param int $userId ID пользователя
     * @return array
     */
    public function getStatistics(int $userId): array
    {
        $goals = Goal::where('user_id', $userId)->get();

        $totalCreated = $goals->count();
        $totalCompleted = $goals->where('status', GoalStatus::COMPLETED)->count();
        $totalFailed = $goals->where('status', GoalStatus::FAILED)->count();
        $totalCancelled = $goals->where('status', GoalStatus::CANCELLED)->count();

        $completionRate = ($totalCompleted + $totalFailed) > 0
            ? round(($totalCompleted / ($totalCompleted + $totalFailed)) * 100, 1)
            : 0.0;

        $completedGoals = $goals->where('status', GoalStatus::COMPLETED)
            ->whereNotNull('days_to_complete');

        $averageDaysToComplete = $completedGoals->isNotEmpty()
            ? round($completedGoals->avg('days_to_complete'), 1)
            : 0.0;

        // Подсчет текущей серии достижений
        $completedGoalsOrdered = $goals->where('status', GoalStatus::COMPLETED)
            ->sortBy('completed_at')
            ->values();

        $currentStreak = 0;
        $longestStreak = 0;
        $tempStreak = 0;

        foreach ($completedGoalsOrdered as $goal) {
            $tempStreak++;
            $longestStreak = max($longestStreak, $tempStreak);
        }

        $currentStreak = $tempStreak;

        // Статистика по типам целей
        $goalsByType = $goals->groupBy('type')->map->count()->toArray();

        // Последние 10 достижений
        $recentAchievements = $goals->where('status', GoalStatus::COMPLETED)
            ->sortByDesc('completed_at')
            ->take(10)
            ->map(function ($goal) {
                return [
                    'id' => $goal->id,
                    'title' => $goal->title,
                    'type' => $goal->type->value,
                    'completed_at' => $goal->completed_at?->toDateString(),
                    'days_to_complete' => $goal->days_to_complete,
                ];
            })
            ->values()
            ->toArray();

        return [
            'total_goals_created' => $totalCreated,
            'total_goals_completed' => $totalCompleted,
            'total_goals_failed' => $totalFailed,
            'total_goals_cancelled' => $totalCancelled,
            'completion_rate' => $completionRate,
            'average_days_to_complete' => $averageDaysToComplete,
            'current_streak' => $currentStreak,
            'longest_streak' => $longestStreak,
            'goals_by_type' => $goalsByType,
            'recent_achievements' => $recentAchievements,
        ];
    }

    // Методы расчета для каждого типа цели

    private function calculateTotalWorkouts(User $user, $startDate, $endDate): float
    {
        return (float) $user->workouts()
            ->whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->count();
    }

    private function calculateCompletedWorkouts(User $user, $startDate, $endDate): float
    {
        return (float) $user->workouts()
            ->whereNotNull('finished_at')
            ->whereDate('finished_at', '>=', $startDate)
            ->whereDate('finished_at', '<=', $endDate)
            ->count();
    }

    private function calculateTargetWeight(User $user): float
    {
        return (float) ($user->current_weight ?? 0);
    }

    private function calculateWeightLoss(User $user, $startDate, $endDate): float
    {
        $startWeight = $user->metrics()
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->first()?->weight;

        $endWeight = $user->current_weight;

        if (!$startWeight || !$endWeight) {
            return 0.0;
        }

        return max(0.0, (float) ($startWeight - $endWeight));
    }

    private function calculateWeightGain(User $user, $startDate, $endDate): float
    {
        $startWeight = $user->metrics()
            ->where('date', '>=', $startDate)
            ->orderBy('date')
            ->first()?->weight;

        $endWeight = $user->current_weight;

        if (!$startWeight || !$endWeight) {
            return 0.0;
        }

        return max(0.0, (float) ($endWeight - $startWeight));
    }

    private function calculateTotalVolume(User $user, $startDate, $endDate): float
    {
        return (float) $user->workoutSets()
            ->whereHas('workout', function ($query) use ($startDate, $endDate) {
                $query->whereDate('finished_at', '>=', $startDate)
                    ->whereDate('finished_at', '<=', $endDate)
                    ->whereNotNull('finished_at');
            })
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->sum(DB::raw('weight * reps'));
    }

    private function calculateWeeklyVolume(User $user, $startDate, $endDate): float
    {
        $weeks = max(1, (int) ceil($startDate->diffInDays($endDate) / 7));
        $totalVolume = $this->calculateTotalVolume($user, $startDate, $endDate);
        return round($totalVolume / $weeks, 2);
    }

    private function calculateTotalTrainingTime(User $user, $startDate, $endDate): float
    {
        return (float) $user->workouts()
            ->whereNotNull('started_at')
            ->whereNotNull('finished_at')
            ->whereDate('finished_at', '>=', $startDate)
            ->whereDate('finished_at', '<=', $endDate)
            ->get()
            ->sum('duration_minutes');
    }

    private function calculateWeeklyTrainingTime(User $user, $startDate, $endDate): float
    {
        $weeks = max(1, (int) ceil($startDate->diffInDays($endDate) / 7));
        $totalTime = $this->calculateTotalTrainingTime($user, $startDate, $endDate);
        return round($totalTime / $weeks, 2);
    }

    private function calculateTrainingFrequency(User $user): float
    {
        $startDate = \Carbon\Carbon::today()->subDays(35)->startOfDay();
        
        $totalWorkouts = $user->workouts()
            ->whereNotNull('finished_at')
            ->whereDate('finished_at', '>=', $startDate)
            ->count();

        return round($totalWorkouts / 5, 1);
    }

    private function calculateExerciseMaxWeight(User $user, ?int $exerciseId): float
    {
        if (!$exerciseId) {
            return 0.0;
        }

        return (float) DB::table('workout_sets')
            ->join('plan_exercises', 'workout_sets.plan_exercise_id', '=', 'plan_exercises.id')
            ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $user->id)
            ->where('plan_exercises.exercise_id', $exerciseId)
            ->whereNotNull('workout_sets.weight')
            ->max('workout_sets.weight') ?? 0.0;
    }

    private function calculateExerciseMaxReps(User $user, ?int $exerciseId): float
    {
        if (!$exerciseId) {
            return 0.0;
        }

        return (float) DB::table('workout_sets')
            ->join('plan_exercises', 'workout_sets.plan_exercise_id', '=', 'plan_exercises.id')
            ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $user->id)
            ->where('plan_exercises.exercise_id', $exerciseId)
            ->whereNotNull('workout_sets.reps')
            ->max('workout_sets.reps') ?? 0.0;
    }

    private function calculateExerciseVolume(User $user, ?int $exerciseId, $startDate, $endDate): float
    {
        if (!$exerciseId) {
            return 0.0;
        }

        return (float) DB::table('workout_sets')
            ->join('plan_exercises', 'workout_sets.plan_exercise_id', '=', 'plan_exercises.id')
            ->join('workouts', 'workout_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $user->id)
            ->where('plan_exercises.exercise_id', $exerciseId)
            ->whereDate('workouts.finished_at', '>=', $startDate)
            ->whereDate('workouts.finished_at', '<=', $endDate)
            ->whereNotNull('workouts.finished_at')
            ->whereNotNull('workout_sets.weight')
            ->whereNotNull('workout_sets.reps')
            ->sum(DB::raw('workout_sets.weight * workout_sets.reps')) ?? 0.0;
    }

    private function getUserNotificationPreferences(int $userId): ?UserNotificationPreference
    {
        return UserNotificationPreference::where('user_id', $userId)->first();
    }

    private function getCurrentMilestone(int $progressPercentage, array $milestones): ?int
    {
        foreach ($milestones as $milestone) {
            if ($progressPercentage >= $milestone) {
                return $milestone;
            }
        }
        return null;
    }
}




