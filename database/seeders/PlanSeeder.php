<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\Plan;
use Illuminate\Database\Seeder;

final class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing cycles
        $cycles = Cycle::all();

        if ($cycles->isEmpty()) {
            $this->command->warn('No cycles found. Please run CycleSeeder first.');
            return;
        }

        $plans = [
            // Планы для базового цикла набора массы
            [
                'cycle_name' => 'Базовый цикл набора массы',
                'name' => 'Тренировка A - Грудь и Трицепс',
                'order' => 1,
            ],
            [
                'cycle_name' => 'Базовый цикл набора массы',
                'name' => 'Тренировка B - Спина и Бицепс',
                'order' => 2,
            ],
            [
                'cycle_name' => 'Базовый цикл набора массы',
                'name' => 'Тренировка C - Ноги и Плечи',
                'order' => 3,
            ],

            // Планы для цикла силовой подготовки
            [
                'cycle_name' => 'Цикл силовой подготовки',
                'name' => 'Силовая тренировка - Жимовые движения',
                'order' => 1,
            ],
            [
                'cycle_name' => 'Цикл силовой подготовки',
                'name' => 'Силовая тренировка - Тяговые движения',
                'order' => 2,
            ],
            [
                'cycle_name' => 'Цикл силовой подготовки',
                'name' => 'Силовая тренировка - Приседания и Становая',
                'order' => 3,
            ],

            // Планы для цикла сушки и рельефа
            [
                'cycle_name' => 'Цикл сушки и рельефа',
                'name' => 'Кардио + Верх тела',
                'order' => 1,
            ],
            [
                'cycle_name' => 'Цикл сушки и рельефа',
                'name' => 'Кардио + Низ тела',
                'order' => 2,
            ],
            [
                'cycle_name' => 'Цикл сушки и рельефа',
                'name' => 'Кардио + Круговая тренировка',
                'order' => 3,
            ],

            // Планы для поддерживающего цикла
            [
                'cycle_name' => 'Поддерживающий цикл',
                'name' => 'Поддерживающая тренировка - Верх тела',
                'order' => 1,
            ],
            [
                'cycle_name' => 'Поддерживающий цикл',
                'name' => 'Поддерживающая тренировка - Низ тела',
                'order' => 2,
            ],

            // Планы для цикла восстановления
            [
                'cycle_name' => 'Цикл восстановления',
                'name' => 'Восстановительная тренировка - Растяжка и МФР',
                'order' => 1,
            ],
            [
                'cycle_name' => 'Цикл восстановления',
                'name' => 'Восстановительная тренировка - Легкое кардио',
                'order' => 2,
            ],
        ];

        foreach ($plans as $planData) {
            $cycle = $cycles->where('name', $planData['cycle_name'])->first();
            
            if ($cycle) {
                Plan::firstOrCreate(
                    [
                        'name' => $planData['name'],
                        'cycle_id' => $cycle->id,
                    ],
                    [
                        'order' => $planData['order'],
                    ]
                );
            }
        }

        // Создаем standalone планы (без цикла)
        $standalonePlans = [
            [
                'name' => 'Базовый план для начинающих',
                'order' => null,
            ],
            [
                'name' => 'План для похудения',
                'order' => null,
            ],
            [
                'name' => 'План для набора массы',
                'order' => null,
            ],
            [
                'name' => 'План для поддержания формы',
                'order' => null,
            ],
            [
                'name' => 'План для восстановления',
                'order' => null,
            ],
        ];

        foreach ($standalonePlans as $planData) {
            Plan::firstOrCreate(
                [
                    'name' => $planData['name'],
                    'cycle_id' => null,
                ],
                [
                    'order' => $planData['order'],
                ]
            );
        }

        $this->command->info('Plan seeder completed successfully.');
    }
}
