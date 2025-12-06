<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\GoalFailedMail;
use App\Models\Goal;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о провале цели
 */
final class GoalFailedNotification extends Notification implements ShouldQueue
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
    public function toMail(object $notifiable): GoalFailedMail
    {
        $frontendUrl = config('app.frontend_url', config('app.url', 'http://localhost'));
        $goalUrl = rtrim($frontendUrl, '/') . '/goals/' . $this->goal->id;
        
        return new GoalFailedMail($this->goal, $goalUrl, $notifiable->email);
    }

    /**
     * Get the FCM representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toFcm(object $notifiable): array
    {
        $currentValue = $this->goal->current_value ?? 0;
        $targetValue = $this->goal->target_value;
        $difference = $targetValue - $currentValue;
        
        return [
            'title' => 'Цель не достигнута',
            'body' => "К сожалению, цель '{$this->goal->title}' не была достигнута. Достигнуто: {$currentValue} из {$targetValue}",
            'data' => [
                'type' => 'goal_failed',
                'goal_id' => $this->goal->id,
                'goal_title' => $this->goal->title,
                'current_value' => $currentValue,
                'target_value' => $targetValue,
                'difference' => $difference,
            ],
        ];
    }
}
