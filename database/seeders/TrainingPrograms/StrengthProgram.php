<?php

declare(strict_types=1);

namespace Database\Seeders\TrainingPrograms;

/**
 * Программа на силу (Powerlifting-style)
 * 
 * Акцент на базовые многосуставные движения.
 */
final class StrengthProgram implements TrainingProgramDataInterface
{
    public function getData(): array
    {
        return [
            'cycles' => [
                [
                    'name' => 'Силовая программа (3 дня в неделю)',
                    'plans' => [
                        [
                            'name' => 'День A: Присед + Жим',
                            'exercises' => [
                                ['name' => 'Приседания со штангой (5x5)', 'muscle_group_id' => 5],
                                ['name' => 'Жим лёжа (5x5)', 'muscle_group_id' => 1],
                                ['name' => 'Тяга штанги в наклоне', 'muscle_group_id' => 2],
                                ['name' => 'Подъём на бицепс', 'muscle_group_id' => 4],
                            ],
                        ],
                        [
                            'name' => 'День B: Становая + Жим стоя',
                            'exercises' => [
                                ['name' => 'Становая тяга (1x5 или 3x3)', 'muscle_group_id' => 2],
                                ['name' => 'Жим штанги стоя (5x5)', 'muscle_group_id' => 3],
                                ['name' => 'Подтягивания', 'muscle_group_id' => 2],
                                ['name' => 'Французский жим', 'muscle_group_id' => 4],
                            ],
                        ],
                        [
                            'name' => 'День A (повтор)',
                            'exercises' => [
                                ['name' => 'Приседания со штангой (прогрессия)', 'muscle_group_id' => 5],
                                ['name' => 'Жим лёжа (прогрессия)', 'muscle_group_id' => 1],
                                ['name' => 'Гиперэкстензия', 'muscle_group_id' => 2],
                                ['name' => 'Молотки', 'muscle_group_id' => 4],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}