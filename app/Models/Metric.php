<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Metric extends Model
{
    protected $fillable = [
        'user_id',
        'date',
        'weight',
        'note',
    ];

    protected $casts = [
        'date' => 'date',
        'weight' => 'decimal:2',
    ];

    /**
     * Get the user that owns the metric.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
