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
            ['name' => 'Грудь'],
            ['name' => 'Спина'],
            ['name' => 'Плечи'],
            ['name' => 'Руки'],
            ['name' => 'Ноги'],
            ['name' => 'Пресс'],
            ['name' => 'Поясница'],
            ['name' => 'Тазовые мышцы'],
            ['name' => 'Мышцы шеи'],
        ];

        foreach ($muscleGroups as $group) {
            MuscleGroup::firstOrCreate($group);
        }
    }
}
