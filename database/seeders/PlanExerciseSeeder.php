<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Exercise;
use App\Models\PlanExercise;
use Illuminate\Database\Seeder;

final class PlanExerciseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing plans and exercises
        $plans = Plan::all();
        $exercises = Exercise::all();

        if ($plans->isEmpty() || $exercises->isEmpty()) {
            $this->command->warn('No plans or exercises found. Please run PlanSeeder and ExerciseSeeder first.');
            return;
        }

        // Create plan exercises for each plan
        foreach ($plans as $plan) {
            // Random number of exercises per plan (3-8)
            $exerciseCount = rand(3, 8);
            
            // Get random exercises for this plan
            $selectedExercises = $exercises->random($exerciseCount);
            
            foreach ($selectedExercises as $index => $exercise) {
                PlanExercise::create([
                    'plan_id' => $plan->id,
                    'exercise_id' => $exercise->id,
                    'order' => $index + 1,
                ]);
            }
        }

        $this->command->info('PlanExercise seeder completed successfully.');
    }
}
