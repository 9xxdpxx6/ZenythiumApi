<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\GoalStatus;
use App\Enums\GoalType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель цели
 *
 * Представляет цель пользователя по тренировкам, весу и другим метрикам.
 *
 * @property int $id ID цели
 * @property int $user_id ID пользователя
 * @property GoalType $type Тип цели
 * @property string $title Название цели
 * @property string|null $description Описание цели
 * @property float $target_value Целевое значение
 * @property \Carbon\Carbon $start_date Дата начала
 * @property \Carbon\Carbon|null $end_date Дата окончания (дедлайн)
 * @property int|null $exercise_id ID упражнения (для целей по упражнениям)
 * @property GoalStatus $status Статус цели
 * @property float|null $current_value Текущее значение (кэшированное)
 * @property int $progress_percentage Процент выполнения (0-100)
 * @property int|null $last_notified_milestone Последняя отправленная веха прогресса
 * @property \Carbon\Carbon|null $last_deadline_reminder_at Последнее напоминание о дедлайне
 * @property \Carbon\Carbon|null $completed_at Дата достижения цели
 * @property float|null $achieved_value Фактически достигнутое значение
 * @property \Carbon\Carbon|null $cancelled_at Дата отмены цели
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 *
 * @property-read \App\Models\User $user Пользователь
 * @property-read \App\Models\Exercise|null $exercise Упражнение (если применимо)
 */
final class Goal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'target_value',
        'start_date',
        'end_date',
        'exercise_id',
        'status',
        'current_value',
        'progress_percentage',
        'last_notified_milestone',
        'last_deadline_reminder_at',
        'completed_at',
        'achieved_value',
        'cancelled_at',
    ];

    protected $casts = [
        'type' => GoalType::class,
        'status' => GoalStatus::class,
        'target_value' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'current_value' => 'decimal:2',
        'progress_percentage' => 'integer',
        'last_notified_milestone' => 'integer',
        'last_deadline_reminder_at' => 'datetime',
        'completed_at' => 'datetime',
        'achieved_value' => 'decimal:2',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = [
        'days_to_complete',
    ];

    /**
     * Получить пользователя цели
     *
     * @return BelongsTo Связь с моделью User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить упражнение цели
     *
     * @return BelongsTo Связь с моделью Exercise
     */
    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    /**
     * Получить количество дней до достижения цели
     *
     * @return int|null Количество дней или null если цель не достигнута
     */
    public function getDaysToCompleteAttribute(): ?int
    {
        if (!$this->completed_at || !$this->start_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->completed_at);
    }

    /**
     * Проверить, достигнута ли цель
     *
     * @return bool
     */
    public function isCompleted(): bool
    {
        return $this->status === GoalStatus::COMPLETED;
    }

    /**
     * Проверить, провалена ли цель
     *
     * @return bool
     */
    public function isFailed(): bool
    {
        return $this->status === GoalStatus::FAILED;
    }

    /**
     * Проверить, активна ли цель
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === GoalStatus::ACTIVE;
    }
}
