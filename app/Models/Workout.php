<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Модель тренировки
 * 
 * Представляет тренировку пользователя, связанную с планом тренировок.
 * Содержит информацию о времени начала и окончания, а также вычисляемые атрибуты.
 * 
 * @property int $id ID тренировки
 * @property int $plan_id ID плана тренировки
 * @property int $user_id ID пользователя
 * @property \Carbon\Carbon|null $started_at Время начала тренировки
 * @property \Carbon\Carbon|null $finished_at Время окончания тренировки
 * @property int|null $duration_minutes Продолжительность тренировки в минутах (вычисляемый атрибут)
 * @property int $exercise_count Количество упражнений в тренировке (вычисляемый атрибут)
 * @property float $total_volume Общий объем тренировки (вес × повторения) (вычисляемый атрибут)
 * @property \Carbon\Carbon $created_at Время создания записи
 * @property \Carbon\Carbon $updated_at Время последнего обновления записи
 * 
 * @property-read \App\Models\Plan $plan План тренировки
 * @property-read \App\Models\User $user Пользователь
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\WorkoutSet[] $workoutSets Подходы тренировки
 */
final class Workout extends Model
{
    use HasFactory;
    protected $fillable = [
        'plan_id',
        'user_id',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    protected $appends = [
        'duration_minutes',
        'exercise_count',
        'total_volume',
    ];

    /**
     * Получить план тренировки
     * 
     * @return BelongsTo Связь с моделью Plan
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Получить пользователя тренировки
     * 
     * @return BelongsTo Связь с моделью User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить подходы тренировки
     * 
     * @return HasMany Связь с коллекцией WorkoutSet
     */
    public function workoutSets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class);
    }

    /**
     * Получить продолжительность тренировки в минутах
     * 
     * Вычисляет разность между временем начала и окончания тренировки.
     * Возвращает null если тренировка не завершена.
     * 
     * @return int|null Продолжительность в минутах или null
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->finished_at);
    }

    /**
     * Получить количество упражнений в тренировке
     * 
     * Подсчитывает уникальные упражнения по plan_exercise_id.
     * Оптимизировано: использует кэширование для избежания повторных запросов.
     * 
     * @return int Количество различных упражнений
     */
    public function getExerciseCountAttribute(): int
    {
        // Используем кэширование через relationship, если уже загружена
        if ($this->relationLoaded('workoutSets')) {
            return $this->workoutSets->pluck('plan_exercise_id')->unique()->count();
        }
        
        // Оптимизированный запрос: используем COUNT(DISTINCT) через selectRaw
        $result = DB::table('workout_sets')
            ->where('workout_id', $this->id)
            ->selectRaw('COUNT(DISTINCT plan_exercise_id) as count')
            ->value('count');
        
        return (int) ($result ?? 0);
    }

    /**
     * Получить общий объем тренировки
     * 
     * Вычисляет сумму произведений веса на количество повторений для всех подходов.
     * Оптимизировано: использует кэширование для избежания повторных запросов.
     * 
     * @return float Общий объем (вес × повторения)
     */
    public function getTotalVolumeAttribute(): float
    {
        // Используем кэширование через relationship, если уже загружена
        if ($this->relationLoaded('workoutSets')) {
            return (float) $this->workoutSets
                ->whereNotNull('weight')
                ->whereNotNull('reps')
                ->sum(fn($set) => (float) $set->weight * (int) $set->reps);
        }
        
        // Оптимизированный запрос: вычисляем в БД
        return (float) $this->workoutSets()
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->sum(DB::raw('weight * reps'));
    }
}
