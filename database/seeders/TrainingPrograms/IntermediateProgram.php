<?php

declare(strict_types=1);

namespace Database\Seeders\TrainingPrograms;

/**
 * Программа для среднего уровня
 * 
 * Четырехдневный сплит с акцентом на прогрессивную нагрузку.
 */
final class IntermediateProgram implements TrainingProgramDataInterface
{
    public function getData(): array
    {
        return [
            'cycles' => [
                [
                    'name' => 'Четырёхдневный сплит (средний уровень)',
                    'plans' => [
                        [
                            'name' => 'День 1: Грудь + Трицепс',
                            'exercises' => [
                                ['name' => 'Жим штанги лёжа', 'muscle_group_id' => 1],
                                ['name' => 'Жим гантелей под углом', 'muscle_group_id' => 1],
                                ['name' => 'Разводка гантелей лёжа', 'muscle_group_id' => 1],
                                ['name' => 'Французский жим', 'muscle_group_id' => 4],
                                ['name' => 'Жим узким хватом', 'muscle_group_id' => 4],
                            ],
                        ],
                        [
                            'name' => 'День 2: Спина + Бицепс',
                            'exercises' => [
                                ['name' => 'Тяга вертикального блока к груди', 'muscle_group_id' => 2],
                                ['name' => 'Тяга штанги в наклоне', 'muscle_group_id' => 2],
                                ['name' => 'Гиперэкстензия', 'muscle_group_id' => 2],
                                ['name' => 'Молотковые подъёмы', 'muscle_group_id' => 4],
                                ['name' => 'Подъём EZ-грифа на бицепс', 'muscle_group_id' => 4],
                            ],
                        ],
                        [
                            'name' => 'День 3: Отдых или кардио',
                            'exercises' => [],
                        ],
                        [
                            'name' => 'День 4: Ноги',
                            'exercises' => [
                                ['name' => 'Приседания со штангой', 'muscle_group_id' => 5],
                                ['name' => 'Румынская тяга', 'muscle_group_id' => 5],
                                ['name' => 'Жим ногами', 'muscle_group_id' => 5],
                                ['name' => 'Сгибание ног лёжа', 'muscle_group_id' => 5],
                                ['name' => 'Икры стоя', 'muscle_group_id' => 6],
                            ],
                        ],
                        [
                            'name' => 'День 5: Плечи + Пресс',
                            'exercises' => [
                                ['name' => 'Жим арнольда', 'muscle_group_id' => 3],
                                ['name' => 'Подъёмы штанги перед собой', 'muscle_group_id' => 3],
                                ['name' => 'Разведение гантелей в наклоне (задние дельты)', 'muscle_group_id' => 3],
                                ['name' => 'Скручивания на пресс', 'muscle_group_id' => 7],
                                ['name' => 'Планка', 'muscle_group_id' => 7],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}