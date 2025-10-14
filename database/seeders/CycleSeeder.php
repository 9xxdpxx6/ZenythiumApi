<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Cycle;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class CycleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user or create a default one for cycles
        $userId = User::first()?->id ?? User::factory()->create()->id;

        $cycles = [
            // Активные циклы (без даты завершения)
            [
                'name' => 'Базовый цикл набора массы',
                'start_date' => Carbon::now()->subWeeks(2),
                'end_date' => null, // Активный цикл
                'weeks' => 12,
            ],
            [
                'name' => 'Цикл силовой подготовки',
                'start_date' => Carbon::now()->subWeeks(1),
                'end_date' => null, // Активный цикл
                'weeks' => 9,
            ],
            // Завершенные циклы
            [
                'name' => 'Цикл сушки и рельефа',
                'start_date' => Carbon::now()->subWeeks(8),
                'end_date' => Carbon::now()->subWeeks(2),
                'weeks' => 6,
            ],
            [
                'name' => 'Поддерживающий цикл',
                'start_date' => Carbon::now()->subWeeks(6),
                'end_date' => Carbon::now()->subWeeks(2),
                'weeks' => 4,
            ],
            [
                'name' => 'Цикл восстановления',
                'start_date' => Carbon::now()->subWeeks(4),
                'end_date' => Carbon::now()->subWeeks(2),
                'weeks' => 2,
            ],
        ];

        foreach ($cycles as $cycleData) {
            Cycle::firstOrCreate(
                [
                    'name' => $cycleData['name'],
                    'user_id' => $userId,
                ],
                [
                    'start_date' => $cycleData['start_date'],
                    'end_date' => $cycleData['end_date'],
                    'weeks' => $cycleData['weeks'],
                ]
            );
        }

        $this->command->info('Cycle seeder completed successfully.');
    }
}
