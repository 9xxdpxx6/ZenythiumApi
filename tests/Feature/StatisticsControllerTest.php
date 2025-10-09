<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Metric;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('StatisticsController', function () {
    describe('GET /api/v1/user/statistics', function () {
        it('returns user statistics', function () {
            // Create test data
            $cycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
            ]);

            $plan = Plan::factory()->create([
                'cycle_id' => $cycle->id,
                'name' => 'Test Plan',
            ]);

            // Create completed workouts
            $workout1 = Workout::factory()->create([
                'plan_id' => $plan->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subHours(2),
                'finished_at' => now()->subHour(),
            ]);

            $workout2 = Workout::factory()->create([
                'plan_id' => $plan->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subHours(3),
                'finished_at' => now()->subHours(1),
            ]);

            // Create incomplete workout
            Workout::factory()->create([
                'plan_id' => $plan->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subMinutes(30),
                'finished_at' => null,
            ]);

            // Create workout sets with volume
            WorkoutSet::factory()->create([
                'workout_id' => $workout1->id,
                'weight' => 100,
                'reps' => 10,
            ]);

            WorkoutSet::factory()->create([
                'workout_id' => $workout2->id,
                'weight' => 80,
                'reps' => 12,
            ]);

            // Create weight metrics
            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => now()->subDays(30),
                'weight' => 70.0,
            ]);

            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => now()->subDays(15),
                'weight' => 72.0,
            ]);

            Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => now(),
                'weight' => 75.0,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/user/statistics');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'total_workouts',
                        'completed_workouts',
                        'total_training_time',
                        'total_volume',
                        'current_weight',
                        'active_cycles_count',
                        'weight_change_30_days',
                        'training_frequency_4_weeks',
                        'training_streak_days',
                    ],
                    'message',
                ]);

            expect($response->json('data.total_workouts'))->toBe(3);
            expect($response->json('data.completed_workouts'))->toBe(2);
            expect($response->json('data.total_training_time'))->toBe(180); // 60 + 120 minutes total
            expect($response->json('data.total_volume'))->toBe(1960); // (100*10) + (80*12)
            expect($response->json('data.current_weight'))->toBe(75);
            expect($response->json('data.active_cycles_count'))->toBe(1);
            expect($response->json('data.weight_change_30_days'))->toBe(5); // 75 - 70
        });

        it('handles empty data gracefully', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/user/statistics');

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'total_workouts' => 0,
                        'completed_workouts' => 0,
                        'total_training_time' => 0,
                        'total_volume' => 0,
                        'current_weight' => null,
                        'active_cycles_count' => 0,
                        'weight_change_30_days' => null,
                        'training_frequency_4_weeks' => 0,
                        'training_streak_days' => 0,
                    ],
                ]);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/v1/user/statistics');

            $response->assertStatus(401);
        });

        it('calculates training streak correctly', function () {
            $cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $plan = Plan::factory()->create(['cycle_id' => $cycle->id]);

            // Create workouts for consecutive days
            Workout::factory()->create([
                'plan_id' => $plan->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDays(2),
                'finished_at' => now()->subDays(2)->addHour(),
            ]);

            Workout::factory()->create([
                'plan_id' => $plan->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDay(),
                'finished_at' => now()->subDay()->addHour(),
            ]);

            Workout::factory()->create([
                'plan_id' => $plan->id,
                'user_id' => $this->user->id,
                'started_at' => now(),
                'finished_at' => now()->addHour(),
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/user/statistics');

            expect($response->json('data.training_streak_days'))->toBe(3);
        });
    });
});
