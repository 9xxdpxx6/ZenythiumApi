<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

final class Cycle extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'name',
        'start_date',
        'end_date',
        'weeks',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'weeks' => 'integer',
    ];

    protected $appends = [
        'progress_percentage',
        'completed_workouts_count',
        'current_week',
    ];

    /**
     * Get the user that owns the cycle.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plans for the cycle.
     */
    public function plans(): HasMany
    {
        return $this->hasMany(Plan::class);
    }

    /**
     * Get the workouts for the cycle through plans.
     */
    public function workouts(): HasManyThrough
    {
        return $this->hasManyThrough(Workout::class, Plan::class);
    }

    /**
     * Get the progress percentage of the cycle based on completed workouts.
     */
    public function getProgressPercentageAttribute(): int
    {
        // Если нет планов в цикле или не указано количество недель, прогресс 0%
        $totalPlans = $this->plans()->count();
        if ($totalPlans === 0 || $this->weeks <= 0) {
            return 0;
        }

        // Считаем количество завершенных тренировок
        $completedWorkouts = $this->workouts()->whereNotNull('finished_at')->count();
        
        // Общее количество запланированных тренировок = количество недель × количество планов
        // Базовая логика: каждый план выполняется раз в неделю
        $totalScheduledWorkouts = $this->weeks * $totalPlans;
        
        // Прогресс = (завершенные тренировки / общее количество запланированных тренировок) * 100
        return min(100, max(0, (int) round(($completedWorkouts / $totalScheduledWorkouts) * 100)));
    }

    /**
     * Get the number of completed workouts in the cycle.
     */
    public function getCompletedWorkoutsCountAttribute(): int
    {
        return $this->workouts()->whereNotNull('finished_at')->count();
    }

    /**
     * Get the current week of the cycle based on progress percentage.
     * Synchronized with progress_percentage to reflect actual workout completion.
     * Returns 0 if no progress, weeks if 100% complete, or calculated week based on progress.
     */
    public function getCurrentWeekAttribute(): int
    {
        // Если нет планов в цикле или не указано количество недель, текущая неделя = 0
        $totalPlans = $this->plans()->count();
        if ($totalPlans === 0 || $this->weeks <= 0) {
            return 0;
        }

        // Получаем прогресс в процентах (используем ту же логику, что и в getProgressPercentageAttribute)
        $completedWorkouts = $this->workouts()->whereNotNull('finished_at')->count();
        $totalScheduledWorkouts = $this->weeks * $totalPlans;
        
        // Если нет завершенных тренировок, текущая неделя = 0
        if ($completedWorkouts === 0 || $totalScheduledWorkouts === 0) {
            return 0;
        }

        // Вычисляем прогресс в процентах
        $progressPercentage = min(100, max(0, ($completedWorkouts / $totalScheduledWorkouts) * 100));
        
        // Если прогресс 100%, текущая неделя = общее количество недель
        if ($progressPercentage >= 100) {
            return $this->weeks;
        }

        // Вычисляем текущую неделю на основе прогресса
        // Округляем вверх: если есть хотя бы минимальный прогресс, это уже неделя 1
        // Например: 6 недель, 50% прогресса = 3 неделя
        $calculatedWeek = (int) ceil(($progressPercentage / 100) * $this->weeks);
        
        // Минимум 1 (если есть прогресс), максимум weeks
        return min($this->weeks, max(1, $calculatedWeek));
    }
}
