<?php

declare(strict_types=1);

namespace Database\Seeders\TrainingPrograms;

/**
 * Интерфейс для данных программы тренировок
 * 
 * Каждая программа тренировок должна реализовать этот интерфейс
 * и предоставить структуру данных программы.
 */
interface TrainingProgramDataInterface
{
    /**
     * Получить данные программы тренировок
     * 
     * @return array Структура данных программы:
     *   [
     *     'cycles' => [
     *       [
     *         'name' => 'Название цикла',
     *         'plans' => [
     *           [
     *             'name' => 'Название плана',
     *             'exercises' => [
     *               [
     *                 'name' => 'Название упражнения',
     *                 'muscle_group_id' => 1,
     *                 'description' => 'Описание упражнения (необязательно)',
     *               ]
     *             ]
     *           ]
     *         ]
     *       ]
     *     ]
     *   ]
     */
    public function getData(): array;
}

