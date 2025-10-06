<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class WorkoutSet extends Model
{
    protected $fillable = [
        'workout_id',
        'plan_exercise_id',
        'weight',
        'reps',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'reps' => 'integer',
    ];

    /**
     * Get the workout that owns the workout set.
     */
    public function workout(): BelongsTo
    {
        return $this->belongsTo(Workout::class);
    }

    /**
     * Get the plan exercise that owns the workout set.
     */
    public function planExercise(): BelongsTo
    {
        return $this->belongsTo(PlanExercise::class);
    }
}
