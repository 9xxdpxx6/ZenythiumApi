<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;

final class MuscleGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $muscleGroups = [
            ['name' => 'Грудь', 'size_factor' => 1.2, 'optimal_frequency_per_week' => 2],
            ['name' => 'Спина', 'size_factor' => 1.5, 'optimal_frequency_per_week' => 2],
            ['name' => 'Плечи', 'size_factor' => 0.8, 'optimal_frequency_per_week' => 2],
            ['name' => 'Руки', 'size_factor' => 1.0, 'optimal_frequency_per_week' => 2],
            ['name' => 'Ноги', 'size_factor' => 1.8, 'optimal_frequency_per_week' => 1], 
            ['name' => 'Пресс', 'size_factor' => 0.8, 'optimal_frequency_per_week' => 3], 
            ['name' => 'Поясница', 'size_factor' => 0.7, 'optimal_frequency_per_week' => 2],
            ['name' => 'Тазовые мышцы', 'size_factor' => 0.6, 'optimal_frequency_per_week' => 2],
            ['name' => 'Мышцы шеи', 'size_factor' => 0.3, 'optimal_frequency_per_week' => 2],
        ];

        foreach ($muscleGroups as $group) {
            MuscleGroup::updateOrCreate(
                ['name' => $group['name']],
                [
                    'size_factor' => $group['size_factor'],
                    'optimal_frequency_per_week' => $group['optimal_frequency_per_week']
                ]
            );
        }
    }
}
