<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

final class WorkoutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing users and plans
        $users = User::all();
        $plans = Plan::all();

        if ($users->isEmpty() || $plans->isEmpty()) {
            $this->command->warn('No users or plans found. Please run User and Plan seeders first.');
            return;
        }

        $workouts = [];

        // Create workouts for the last 60 days (more history)
        for ($i = 0; $i < 60; $i++) {
            $date = Carbon::now()->subDays($i);
            
            // Higher chance of having workouts (50% chance instead of 33%)
            if (rand(1, 2) === 1) { // 50% chance of having a workout on any given day
                $user = $users->random();
                $plan = $plans->random();
                
                // Random workout time between 6 AM and 10 PM
                $startTime = $date->copy()->setTime(rand(6, 22), rand(0, 59));
                $endTime = $startTime->copy()->addMinutes(rand(45, 120)); // 45-120 minutes workout
                
                $workouts[] = [
                    'plan_id' => $plan->id,
                    'user_id' => $user->id,
                    'started_at' => $startTime,
                    'finished_at' => $endTime,
                ];
            }
        }

        // Create some ongoing workouts (started but not finished)
        for ($i = 0; $i < 5; $i++) { // More ongoing workouts
            $user = $users->random();
            $plan = $plans->random();
            $startTime = Carbon::now()->subMinutes(rand(10, 60));
            
            $workouts[] = [
                'plan_id' => $plan->id,
                'user_id' => $user->id,
                'started_at' => $startTime,
                'finished_at' => null, // Ongoing workout
            ];
        }

        foreach ($workouts as $workoutData) {
            Workout::create($workoutData);
        }

        $this->command->info('Workout seeder completed successfully.');
    }
}
