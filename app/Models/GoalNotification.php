<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель истории отправленных уведомлений о целях
 *
 * Используется для предотвращения дубликатов уведомлений.
 *
 * @property int $id ID записи
 * @property int $goal_id ID цели
 * @property string $notification_type Тип уведомления (achieved, progress, deadline_reminder, failed)
 * @property int|null $milestone Веха прогресса (25, 50, 75, 90) для типа progress
 * @property \Carbon\Carbon $sent_at Время отправки
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 *
 * @property-read \App\Models\Goal $goal Цель
 */
final class GoalNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'goal_id',
        'notification_type',
        'milestone',
        'sent_at',
    ];

    protected $casts = [
        'milestone' => 'integer',
        'sent_at' => 'datetime',
    ];

    /**
     * Получить цель
     *
     * @return BelongsTo Связь с моделью Goal
     */
    public function goal(): BelongsTo
    {
        return $this->belongsTo(Goal::class);
    }
}
