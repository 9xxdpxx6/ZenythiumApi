<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\GoalDeadlineReminderMail;
use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Напоминание о дедлайне цели
 */
final class GoalDeadlineReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Goal $goal,
        public readonly int $daysRemaining
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];
        
        // Добавляем FCM канал если есть device tokens
        if ($notifiable->deviceTokens()->exists()) {
            $channels[] = 'fcm';
        }
        
        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): GoalDeadlineReminderMail
    {
        $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost'));
        $goalUrl = rtrim($frontendUrl, '/') . '/goals/' . $this->goal->id;
        
        return new GoalDeadlineReminderMail($this->goal, $this->daysRemaining, $goalUrl, $notifiable->email);
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        $daysText = $this->daysRemaining === 1 ? 'день' : ($this->daysRemaining < 5 ? 'дня' : 'дней');
        
        return [
            'title' => "⏰ До дедлайна цели осталось {$this->daysRemaining} {$daysText}",
            'body' => "Не забудьте про свою цель: {$this->goal->title}. Прогресс: {$this->goal->progress_percentage}%",
            'data' => [
                'type' => 'goal_deadline_reminder',
                'goal_id' => $this->goal->id,
                'goal_title' => $this->goal->title,
                'days_remaining' => $this->daysRemaining,
                'progress_percentage' => $this->goal->progress_percentage,
            ],
        ];
    }
}
