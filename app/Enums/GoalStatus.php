<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Статус цели
 */
enum GoalStatus: string
{
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Получить все значения enum
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}


