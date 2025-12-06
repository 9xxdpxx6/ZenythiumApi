<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Models\GoalNotification;
use App\Notifications\GoalDeadlineReminderNotification;
use App\Services\GoalService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для проверки дедлайнов целей и отправки напоминаний
 */
final class CheckGoalsDeadlinesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(GoalService $goalService): void
    {
        $now = now();
        
        // Получаем активные цели с дедлайном
        $activeGoals = Goal::where('status', GoalStatus::ACTIVE)
            ->whereNotNull('end_date')
            ->get();

        foreach ($activeGoals as $goal) {
            try {
                // Проверяем просроченные цели
                if ($goal->end_date && $goal->end_date->isPast()) {
                    $goalService->markAsFailed($goal);
                    continue;
                }

                // Проверяем напоминания о дедлайне
                $this->checkDeadlineReminders($goal, $goalService);
            } catch (\Exception $e) {
                Log::error('Error checking goal deadline', [
                    'goal_id' => $goal->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Проверить и отправить напоминания о дедлайне
     */
    private function checkDeadlineReminders(Goal $goal, GoalService $goalService): void
    {
        if (!$goal->end_date) {
            return;
        }

        $user = $goal->user;
        $preferences = $user->notificationPreferences;
        
        if (!$preferences || !$preferences->goal_deadline_reminder_enabled) {
            return;
        }

        $reminderDays = $preferences->goal_deadline_reminder_days ?? [7, 3, 1];
        $daysUntilDeadline = now()->diffInDays($goal->end_date, false);

        foreach ($reminderDays as $days) {
            if ($daysUntilDeadline === $days) {
                // Проверяем, не было ли уже отправлено напоминание за эти дни
                $alreadySent = GoalNotification::where('goal_id', $goal->id)
                    ->where('notification_type', 'deadline_reminder')
                    ->whereDate('sent_at', '>=', now()->subDays(1))
                    ->exists();

                if ($alreadySent) {
                    continue;
                }

                // Проверяем, не было ли отправлено напоминание в этот день ранее
                $lastReminder = $goal->last_deadline_reminder_at;
                if ($lastReminder && $lastReminder->isToday()) {
                    continue;
                }

                $user->notify(new GoalDeadlineReminderNotification($goal, $days));

                // Обновляем время последнего напоминания
                $goal->last_deadline_reminder_at = now();
                $goal->save();

                // Сохраняем в историю
                GoalNotification::create([
                    'goal_id' => $goal->id,
                    'notification_type' => 'deadline_reminder',
                    'milestone' => null,
                    'sent_at' => now(),
                ]);

                break; // Отправляем только одно напоминание за раз
            }
        }
    }
}
