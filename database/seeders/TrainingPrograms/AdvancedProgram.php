<?php

declare(strict_types=1);

namespace Database\Seeders\TrainingPrograms;

/**
 * Программа для продвинутых атлетов
 * 
 * Пятидневный сплит с изоляцией и техническими упражнениями.
 */
final class AdvancedProgram implements TrainingProgramDataInterface
{
    public function getData(): array
    {
        return [
            'cycles' => [
                [
                    'name' => 'Пятидневный сплит (продвинутый уровень)',
                    'plans' => [
                        [
                            'name' => 'День 1: Грудь',
                            'exercises' => [
                                ['name' => 'Жим лёжа (сила)', 'muscle_group_id' => 1],
                                ['name' => 'Жим гантелей на наклонной скамье', 'muscle_group_id' => 1],
                                ['name' => 'Кроссовер', 'muscle_group_id' => 1],
                                ['name' => 'Пулловер', 'muscle_group_id' => 1],
                            ],
                        ],
                        [
                            'name' => 'День 2: Спина',
                            'exercises' => [
                                ['name' => 'Становая тяга', 'muscle_group_id' => 2],
                                ['name' => 'Подтягивания с весом', 'muscle_group_id' => 2],
                                ['name' => 'Тяга Т-грифа', 'muscle_group_id' => 2],
                                ['name' => 'Шраги со штангой', 'muscle_group_id' => 2],
                            ],
                        ],
                        [
                            'name' => 'День 3: Ноги',
                            'exercises' => [
                                ['name' => 'Приседания фронтальные', 'muscle_group_id' => 5],
                                ['name' => 'Болгарские выпады', 'muscle_group_id' => 5],
                                ['name' => 'Разгибания ног сидя', 'muscle_group_id' => 5],
                                ['name' => 'Подъёмы на носки сидя', 'muscle_group_id' => 6],
                            ],
                        ],
                        [
                            'name' => 'День 4: Плечи',
                            'exercises' => [
                                ['name' => 'Жим штанги стоя', 'muscle_group_id' => 3],
                                ['name' => 'Обратные разведения в тренажёре', 'muscle_group_id' => 3],
                                ['name' => 'Подъёмы гантелей через стороны', 'muscle_group_id' => 3],
                                ['name' => 'Лицевая тяга', 'muscle_group_id' => 3],
                            ],
                        ],
                        [
                            'name' => 'День 5: Руки (бицепс/трицепс)',
                            'exercises' => [
                                ['name' => 'Суперсет: подъём на бицепс + французский жим', 'muscle_group_id' => 4],
                                ['name' => 'Молотки с гантелями', 'muscle_group_id' => 4],
                                ['name' => 'Отжимания на брусьях с весом', 'muscle_group_id' => 4],
                                ['name' => 'Разгибание каната за голову', 'muscle_group_id' => 4],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}