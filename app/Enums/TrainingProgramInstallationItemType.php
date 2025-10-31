<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Тип элемента установки программы тренировок
 */
enum TrainingProgramInstallationItemType: string
{
    case EXERCISE = 'exercise';
    case PLAN = 'plan';
    case CYCLE = 'cycle';

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

