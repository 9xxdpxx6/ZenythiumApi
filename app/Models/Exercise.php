<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Exercise extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'muscle_group_id',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Get the muscle group that owns the exercise.
     */
    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }

    /**
     * Get the user that created the exercise.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plan exercises for the exercise.
     */
    public function planExercises(): HasMany
    {
        return $this->hasMany(PlanExercise::class);
    }
}
