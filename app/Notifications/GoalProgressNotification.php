<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\GoalProgressMail;
use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о прогрессе цели (вехи)
 */
final class GoalProgressNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Goal $goal,
        public readonly int $milestone
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
    public function toMail(object $notifiable): GoalProgressMail
    {
        $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost'));
        $goalUrl = rtrim($frontendUrl, '/') . '/goals/' . $this->goal->id;
        
        return new GoalProgressMail($this->goal, $this->milestone, $goalUrl, $notifiable->email);
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        $remaining = max(0, $this->goal->target_value - ($this->goal->current_value ?? 0));
        
        return [
            'title' => "Отличный прогресс! {$this->milestone}% до цели",
            'body' => "Вы уже на {$this->milestone}% пути к цели: {$this->goal->title}. Осталось: {$remaining}",
            'data' => [
                'type' => 'goal_progress',
                'goal_id' => $this->goal->id,
                'goal_title' => $this->goal->title,
                'milestone' => $this->milestone,
                'progress_percentage' => $this->goal->progress_percentage,
            ],
        ];
    }
}
