<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MuscleGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'size_factor',
        'optimal_frequency_per_week',
    ];

    /**
     * Get the exercises for the muscle group.
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
