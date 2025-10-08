<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
    ];

    /**
     * Get the user that owns the cycle.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the workouts for the cycle.
     */
    public function workouts(): HasMany
    {
        return $this->hasMany(Workout::class);
    }

    /**
     * Get the progress percentage of the cycle.
     */
    public function getProgressPercentageAttribute(): int
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }

        $totalDays = $this->start_date->diffInDays($this->end_date);
        if ($totalDays === 0) {
            return 100;
        }

        $passedDays = $this->start_date->diffInDays(now());
        
        return min(100, max(0, (int) round(($passedDays / $totalDays) * 100)));
    }

    /**
     * Get the number of completed workouts in the cycle.
     */
    public function getCompletedWorkoutsCountAttribute(): int
    {
        return $this->workouts()->whereNotNull('finished_at')->count();
    }
}
