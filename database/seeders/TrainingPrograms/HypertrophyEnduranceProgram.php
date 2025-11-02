<?php

declare(strict_types=1);

namespace Database\Seeders\TrainingPrograms;

/**
 * Программа на гипертрофию и выносливость
 * 
 * Высокий объём, короткие отдыхи, круговые тренировки.
 */
final class HypertrophyEnduranceProgram implements TrainingProgramDataInterface
{
    public function getData(): array
    {
        return [
            'cycles' => [
                [
                    'name' => 'Гипертрофия + Выносливость (Full Body 3x/week)',
                    'plans' => [
                        [
                            'name' => 'День 1: Full Body Circuit',
                            'exercises' => [
                                ['name' => 'Приседания с гантелями (15–20 повторений)', 'muscle_group_id' => 5],
                                ['name' => 'Отжимания (до отказа)', 'muscle_group_id' => 1],
                                ['name' => 'Тяга гантели в наклоне', 'muscle_group_id' => 2],
                                ['name' => 'Жим гантелей сидя', 'muscle_group_id' => 3],
                                ['name' => 'Скручивания на пресс', 'muscle_group_id' => 7],
                            ],
                        ],
                        [
                            'name' => 'День 2: Кардио + Круговая тренировка',
                            'exercises' => [
                                ['name' => 'Бёрпи (3 раунда по 10)', 'muscle_group_id' => 8], // Полное тело
                                ['name' => 'Выпады с гантелями', 'muscle_group_id' => 5],
                                ['name' => 'Планка с подтягиванием коленей', 'muscle_group_id' => 7],
                                ['name' => 'Альпинисты', 'muscle_group_id' => 8],
                            ],
                        ],
                        [
                            'name' => 'День 3: Full Body Volume',
                            'exercises' => [
                                ['name' => 'Жим лёжа (4x12)', 'muscle_group_id' => 1],
                                ['name' => 'Румынская тяга (4x15)', 'muscle_group_id' => 5],
                                ['name' => 'Подтягивания (или австралийские)', 'muscle_group_id' => 2],
                                ['name' => 'Махи гантелями', 'muscle_group_id' => 3],
                                ['name' => 'Сгибание ног лёжа', 'muscle_group_id' => 5],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}