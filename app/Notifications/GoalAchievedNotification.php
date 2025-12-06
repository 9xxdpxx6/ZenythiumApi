<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\GoalAchievedMail;
use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о достижении цели
 */
final class GoalAchievedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Goal $goal
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
    public function toMail(object $notifiable): GoalAchievedMail
    {
        $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost'));
        $goalUrl = rtrim($frontendUrl, '/') . '/goals/' . $this->goal->id;
        
        return new GoalAchievedMail($this->goal, $goalUrl, $notifiable->email);
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        return [
            'title' => '🎉 Поздравляем! Вы достигли цели',
            'body' => "Вы успешно достигли цели: {$this->goal->title}",
            'data' => [
                'type' => 'goal_achieved',
                'goal_id' => $this->goal->id,
                'goal_title' => $this->goal->title,
                'achieved_value' => $this->goal->achieved_value,
            ],
        ];
    }
}
