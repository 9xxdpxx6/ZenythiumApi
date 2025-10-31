<?php

declare(strict_types=1);

namespace Database\Seeders\TrainingPrograms;

/**
 * Пример программы для новичков
 * 
 * Это базовый пример программы тренировок.
 * Используйте этот файл как шаблон для создания новых программ.
 */
final class BeginnerProgram implements TrainingProgramDataInterface
{
    /**
     * Получить данные программы тренировок
     */
    public function getData(): array
    {
        // Получаем ID групп мышц по названиям (предполагаем что они есть в БД)
        // В реальной реализации можно использовать кеш или константы
        
        return [
            'cycles' => [
                [
                    'name' => 'Базовый цикл для новичков',
                    'plans' => [
                        [
                            'name' => 'День 1: Грудь и трицепс',
                            'exercises' => [
                                [
                                    'name' => 'Жим лежа',
                                    'muscle_group_id' => 1, // Грудь
                                    'description' => 'Базовое упражнение для развития грудных мышц',
                                ],
                                [
                                    'name' => 'Отжимания от пола',
                                    'muscle_group_id' => 1, // Грудь
                                ],
                                [
                                    'name' => 'Отжимания на брусьях',
                                    'muscle_group_id' => 4, // Руки (трицепс)
                                ],
                            ],
                        ],
                        [
                            'name' => 'День 2: Спина и бицепс',
                            'exercises' => [
                                [
                                    'name' => 'Подтягивания',
                                    'muscle_group_id' => 2, // Спина
                                ],
                                [
                                    'name' => 'Тяга штанги в наклоне',
                                    'muscle_group_id' => 2, // Спина
                                ],
                                [
                                    'name' => 'Подъем штанги на бицепс',
                                    'muscle_group_id' => 4, // Руки
                                ],
                            ],
                        ],
                        [
                            'name' => 'День 3: Ноги и плечи',
                            'exercises' => [
                                [
                                    'name' => 'Приседания',
                                    'muscle_group_id' => 5, // Ноги
                                ],
                                [
                                    'name' => 'Жим штанги стоя',
                                    'muscle_group_id' => 3, // Плечи
                                ],
                                [
                                    'name' => 'Махи гантелями в стороны',
                                    'muscle_group_id' => 3, // Плечи
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}

