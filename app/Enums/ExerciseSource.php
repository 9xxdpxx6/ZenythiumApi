<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Источник создания упражнения
 * 
 * Определяет, каким способом упражнение было добавлено в аккаунт пользователя.
 * Используется для трекинга установленных пакетов и возможности отката.
 */
enum ExerciseSource: string
{
    /** Установлено из базового пакета упражнений */
    case BASE_PACK = 'base_pack';

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
