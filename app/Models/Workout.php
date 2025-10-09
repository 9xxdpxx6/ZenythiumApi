<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

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
     * Get the plan that owns the workout.
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /**
     * Get the user that owns the workout.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workout sets for the workout.
     */
    public function workoutSets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class);
    }

    /**
     * Get the workout duration in minutes.
     */
    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->started_at || !$this->finished_at) {
            return null;
        }

        return (int) $this->started_at->diffInMinutes($this->finished_at);
    }

    /**
     * Get the number of exercises in the workout.
     */
    public function getExerciseCountAttribute(): int
    {
        return $this->workoutSets()->distinct('plan_exercise_id')->count();
    }

    /**
     * Get the total volume of the workout (weight × reps).
     */
    public function getTotalVolumeAttribute(): float
    {
        return (float) $this->workoutSets()
            ->whereNotNull('weight')
            ->whereNotNull('reps')
            ->sum(DB::raw('weight * reps'));
    }
}
