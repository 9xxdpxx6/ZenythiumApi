<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MuscleGroup extends Model
{
    protected $fillable = [
        'name',
    ];

    /**
     * Get the exercises for the muscle group.
     */
    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }
}
