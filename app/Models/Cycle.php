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
        $totalPlans = $this->plans_count ?? ($this->relationLoaded('plans') ? $this->plans->count() : $this->plans()->count());
        
        if ($totalPlans === 0 || $this->weeks <= 0) {
            return 0;
        }

        $completedWorkouts = array_key_exists('completed_workouts_count', $this->attributes) 
            ? (int) $this->attributes['completed_workouts_count']
            : $this->workouts()->whereNotNull('finished_at')->count();
        
        $totalScheduledWorkouts = $this->weeks * $totalPlans;
        
        return min(100, max(0, (int) round(($completedWorkouts / $totalScheduledWorkouts) * 100)));
    }

    /**
     * Get the number of completed workouts in the cycle.
     */
    public function getCompletedWorkoutsCountAttribute(): int
    {
        if (array_key_exists('completed_workouts_count', $this->attributes)) {
            return (int) $this->attributes['completed_workouts_count'];
        }
        
        return $this->workouts()->whereNotNull('finished_at')->count();
    }

    /**
     * Get the current week of the cycle based on progress percentage.
     * Synchronized with progress_percentage to reflect actual workout completion.
     * Returns 0 if no progress, weeks if 100% complete, or calculated week based on progress.
     */
    public function getCurrentWeekAttribute(): int
    {
        $totalPlans = $this->plans_count ?? ($this->relationLoaded('plans') ? $this->plans->count() : $this->plans()->count());
        
        if ($totalPlans === 0 || $this->weeks <= 0) {
            return 0;
        }

        $completedWorkouts = array_key_exists('completed_workouts_count', $this->attributes) 
            ? (int) $this->attributes['completed_workouts_count']
            : $this->workouts()->whereNotNull('finished_at')->count();
        $totalScheduledWorkouts = $this->weeks * $totalPlans;
        
        if ($completedWorkouts === 0 || $totalScheduledWorkouts === 0) {
            return 0;
        }

        $progressPercentage = min(100, max(0, ($completedWorkouts / $totalScheduledWorkouts) * 100));
        
        if ($progressPercentage >= 100) {
            return $this->weeks;
        }

        $calculatedWeek = (int) ceil(($progressPercentage / 100) * $this->weeks);
        
        return min($this->weeks, max(1, $calculatedWeek));
    }
}
