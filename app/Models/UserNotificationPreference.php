<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель настроек уведомлений пользователя
 *
 * @property int $id ID настройки
 * @property int $user_id ID пользователя
 * @property bool $goal_achieved_enabled Включены ли уведомления о достижении цели
 * @property bool $goal_progress_enabled Включены ли уведомления о прогрессе
 * @property bool $goal_deadline_reminder_enabled Включены ли напоминания о дедлайне
 * @property bool $goal_failed_enabled Включены ли уведомления о провале цели
 * @property array $goal_progress_milestones Пороги прогресса для уведомлений (25, 50, 75, 90)
 * @property array $goal_deadline_reminder_days За сколько дней напоминать о дедлайне (7, 3, 1)
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 *
 * @property-read \App\Models\User $user Пользователь
 */
final class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'goal_achieved_enabled',
        'goal_progress_enabled',
        'goal_deadline_reminder_enabled',
        'goal_failed_enabled',
        'goal_progress_milestones',
        'goal_deadline_reminder_days',
    ];

    protected $casts = [
        'goal_achieved_enabled' => 'boolean',
        'goal_progress_enabled' => 'boolean',
        'goal_deadline_reminder_enabled' => 'boolean',
        'goal_failed_enabled' => 'boolean',
        'goal_progress_milestones' => 'array',
        'goal_deadline_reminder_days' => 'array',
    ];

    protected $attributes = [
        'goal_achieved_enabled' => true,
        'goal_progress_enabled' => true,
        'goal_deadline_reminder_enabled' => true,
        'goal_failed_enabled' => true,
    ];

    /**
     * Boot метод для установки значений по умолчанию
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($preference) {
            if ($preference->goal_progress_milestones === null) {
                $preference->goal_progress_milestones = [25, 50, 75, 90];
            }
            if ($preference->goal_deadline_reminder_days === null) {
                $preference->goal_deadline_reminder_days = [7, 3, 1];
            }
        });
    }

    /**
     * Получить пользователя
     *
     * @return BelongsTo Связь с моделью User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
