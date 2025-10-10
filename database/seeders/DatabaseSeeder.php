<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'QWE',
            'email' => 'qwe@qwe.qwe',
            'password' => Hash::make('qwe')
        ]);

        $this->call([
            MuscleGroupSeeder::class,
            ExerciseSeeder::class,
            CycleSeeder::class,
            PlanSeeder::class,
            PlanExerciseSeeder::class,
            WorkoutSeeder::class,
            WorkoutSetSeeder::class,
            MetricSeeder::class,
        ]);
    }
}