<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

final class Plan extends Model
{
    use HasFactory;
    protected $fillable = [
        'cycle_id',
        'name',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    protected $appends = [
        'exercise_count',
    ];

    /**
     * Get the cycle that owns the plan.
     */
    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class);
    }

    /**
     * Get the plan exercises for the plan.
     */
    public function planExercises(): HasMany
    {
        return $this->hasMany(PlanExercise::class);
    }

    /**
     * Get the workouts for the plan.
     */
    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    /**
     * Get the workout sets for the plan.
     */
    public function workoutSets(): HasManyThrough
    {
        return $this->hasManyThrough(WorkoutSet::class, PlanExercise::class);
    }

    /**
     * Get the number of exercises in the plan.
     */
    public function getExerciseCountAttribute(): int
    {
        return $this->planExercises()->count();
    }
}
