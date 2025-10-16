<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PlanExercise;
use App\Models\Workout;
use App\Models\WorkoutSet;
use Illuminate\Database\Seeder;

final class WorkoutSetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing workouts and plan exercises
        $workouts = Workout::all();
        $planExercises = PlanExercise::all();

        if ($workouts->isEmpty() || $planExercises->isEmpty()) {
            $this->command->warn('No workouts or plan exercises found. Please run Workout and PlanExercise seeders first.');
            return;
        }

        foreach ($workouts as $workout) {
            // Get plan exercises for this workout's plan
            $planExercisesForWorkout = $planExercises->where('plan_id', $workout->plan_id);
            
            if ($planExercisesForWorkout->isEmpty()) {
                continue;
            }

            // Create workout sets for ALL exercises in the plan (not random selection)
            foreach ($planExercisesForWorkout as $planExercise) {
                // Create 5+ sets per exercise for better history data
                $setCount = rand(5, 8); // 5-8 sets per exercise
                
                for ($set = 1; $set <= $setCount; $set++) {
                    // Progressive weight increase for each set (realistic training)
                    $baseWeight = $this->getBaseWeightForExercise($planExercise->exercise->name ?? 'Unknown');
                    $weightIncrease = ($set - 1) * rand(2, 5); // 2-5 kg increase per set
                    $weight = $baseWeight + $weightIncrease;
                    
                    // Reps decrease as weight increases (realistic training pattern)
                    $baseReps = rand(8, 12);
                    $repsDecrease = ($set - 1) * rand(1, 2); // 1-2 reps decrease per set
                    $reps = max(1, $baseReps - $repsDecrease);

                    WorkoutSet::create([
                        'workout_id' => $workout->id,
                        'plan_exercise_id' => $planExercise->id,
                        'weight' => $weight,
                        'reps' => $reps,
                    ]);
                }
            }
        }

        $this->command->info('WorkoutSet seeder completed successfully.');
    }

    /**
     * Get base weight for different types of exercises
     */
    private function getBaseWeightForExercise(string $exerciseName): float
    {
        $exerciseName = mb_strtolower($exerciseName);
        
        // Different base weights for different exercise types
        if (str_contains($exerciseName, 'жим') && str_contains($exerciseName, 'штанги')) {
            return rand(60, 100); // Barbell press
        } elseif (str_contains($exerciseName, 'жим') && str_contains($exerciseName, 'гантел')) {
            return rand(20, 40); // Dumbbell press
        } elseif (str_contains($exerciseName, 'приседания')) {
            return rand(80, 120); // Squats
        } elseif (str_contains($exerciseName, 'тяга') && str_contains($exerciseName, 'штанги')) {
            return rand(50, 80); // Barbell row
        } elseif (str_contains($exerciseName, 'тяга') && str_contains($exerciseName, 'гантел')) {
            return rand(25, 45); // Dumbbell row
        } elseif (str_contains($exerciseName, 'сгибание') && str_contains($exerciseName, 'штанги')) {
            return rand(20, 35); // Barbell curl
        } elseif (str_contains($exerciseName, 'сгибание') && str_contains($exerciseName, 'гантел')) {
            return rand(8, 15); // Dumbbell curl
        } elseif (str_contains($exerciseName, 'французский жим')) {
            return rand(15, 30); // French press
        } elseif (str_contains($exerciseName, 'махи')) {
            return rand(5, 12); // Lateral raises
        } elseif (str_contains($exerciseName, 'выпады')) {
            return rand(15, 30); // Lunges
        } elseif (str_contains($exerciseName, 'жим платформы')) {
            return rand(100, 200); // Leg press
        } elseif (str_contains($exerciseName, 'сгибание ног') || str_contains($exerciseName, 'разгибание ног')) {
            return rand(20, 50); // Leg curl/extension
        } else {
            return rand(10, 30); // Default weight
        }
    }
}
