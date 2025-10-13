<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\Metric;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    
    $this->muscleGroup = MuscleGroup::factory()->create();
    $this->exercise = Exercise::factory()->create([
        'user_id' => $this->user->id,
        'muscle_group_id' => $this->muscleGroup->id,
    ]);
    
    $this->cycle = Cycle::factory()->create([
        'user_id' => $this->user->id,
    ]);
    
    $this->plan = Plan::factory()->create([
        'cycle_id' => $this->cycle->id,
    ]);
    
    $this->planExercise = PlanExercise::factory()->create([
        'plan_id' => $this->plan->id,
        'exercise_id' => $this->exercise->id,
    ]);
});

test('can get exercise statistics', function () {
    // Создаем тренировку с подходами
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'started_at' => now()->subDays(1),
        'finished_at' => now()->subDays(1)->addMinutes(60),
    ]);
    
    WorkoutSet::factory()->create([
        'workout_id' => $workout->id,
        'plan_exercise_id' => $this->planExercise->id,
        'weight' => 100.0,
        'reps' => 10,
    ]);
    
    WorkoutSet::factory()->create([
        'workout_id' => $workout->id,
        'plan_exercise_id' => $this->planExercise->id,
        'weight' => 105.0,
        'reps' => 8,
    ]);

    $response = $this->getJson('/api/v1/user/exercise-statistics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'top_exercises' => [
                    '*' => [
                        'exercise_name',
                        'muscle_group',
                        'total_sets',
                        'total_volume',
                        'max_weight',
                        'avg_weight',
                        'last_performed',
                    ]
                ],
                'exercise_progress' => [
                    '*' => [
                        'exercise_name',
                        'muscle_group',
                        'weight_progression' => [
                            '*' => [
                                'date',
                                'max_weight',
                                'total_volume',
                            ]
                        ]
                    ]
                ]
            ],
            'message'
        ]);
});

test('can get time analytics', function () {
    // Создаем тренировки в разные дни недели
    $mondayWorkout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'started_at' => now()->startOfWeek(),
        'finished_at' => now()->startOfWeek()->addMinutes(45),
    ]);
    
    $fridayWorkout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'started_at' => now()->startOfWeek()->addDays(4),
        'finished_at' => now()->startOfWeek()->addDays(4)->addMinutes(60),
    ]);

    $response = $this->getJson('/api/v1/user/time-analytics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'weekly_pattern' => [
                    '*' => [
                        'day_of_week',
                        'workout_count',
                        'avg_duration',
                        'total_volume',
                    ]
                ],
                'monthly_trends' => [
                    '*' => [
                        'month',
                        'workout_count',
                        'total_volume',
                        'avg_duration',
                    ]
                ],
                'volume_trends' => [
                    '*' => [
                        'week',
                        'total_volume',
                        'workout_count',
                    ]
                ]
            ],
            'message'
        ]);
});

test('can get muscle group statistics', function () {
    // Создаем тренировку с подходами
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'started_at' => now()->subDays(1),
        'finished_at' => now()->subDays(1)->addMinutes(60),
    ]);
    
    WorkoutSet::factory()->create([
        'workout_id' => $workout->id,
        'plan_exercise_id' => $this->planExercise->id,
        'weight' => 100.0,
        'reps' => 10,
    ]);

    $response = $this->getJson('/api/v1/user/muscle-group-statistics');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'muscle_group_stats' => [
                    '*' => [
                        'muscle_group_name',
                        'total_volume',
                        'workout_count',
                        'exercise_count',
                        'avg_volume_per_workout',
                        'last_trained',
                    ]
                ],
                'balance_analysis' => [
                    'most_trained',
                    'least_trained',
                    'balance_score',
                    'recommendations',
                ]
            ],
            'message'
        ]);
});

test('can get records', function () {
    // Создаем тренировку с подходами
    $workout = Workout::factory()->create([
        'user_id' => $this->user->id,
        'plan_id' => $this->plan->id,
        'started_at' => now()->subDays(1),
        'finished_at' => now()->subDays(1)->addMinutes(60),
    ]);
    
    WorkoutSet::factory()->create([
        'workout_id' => $workout->id,
        'plan_exercise_id' => $this->planExercise->id,
        'weight' => 100.0,
        'reps' => 10,
    ]);
    
    WorkoutSet::factory()->create([
        'workout_id' => $workout->id,
        'plan_exercise_id' => $this->planExercise->id,
        'weight' => 105.0,
        'reps' => 8,
    ]);

    $response = $this->getJson('/api/v1/user/records');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'data' => [
                'personal_records' => [
                    '*' => [
                        'exercise_name',
                        'muscle_group',
                        'max_weight',
                        'max_reps',
                        'max_volume',
                        'achieved_date',
                    ]
                ],
                'workout_records' => [
                    'max_volume_workout' => [
                        'date',
                        'total_volume',
                        'duration_minutes',
                    ],
                    'longest_workout' => [
                        'date',
                        'duration_minutes',
                        'total_volume',
                    ],
                    'most_exercises_workout' => [
                        'date',
                        'exercise_count',
                        'total_volume',
                    ]
                ]
            ],
            'message'
        ]);
});

test('exercise statistics returns empty arrays when no data', function () {
    $response = $this->getJson('/api/v1/user/exercise-statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'top_exercises' => [],
                'exercise_progress' => [],
            ]
        ]);
});

test('time analytics returns empty arrays when no data', function () {
    $response = $this->getJson('/api/v1/user/time-analytics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'weekly_pattern' => [],
                'monthly_trends' => [],
                'volume_trends' => [],
            ]
        ]);
});

test('muscle group statistics returns empty arrays when no data', function () {
    $response = $this->getJson('/api/v1/user/muscle-group-statistics');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'muscle_group_stats' => [],
                'balance_analysis' => [
                    'most_trained' => null,
                    'least_trained' => null,
                    'balance_score' => 0,
                    'recommendations' => [],
                ]
            ]
        ]);
});

test('records returns empty arrays when no data', function () {
    $response = $this->getJson('/api/v1/user/records');

    $response->assertStatus(200)
        ->assertJson([
            'data' => [
                'personal_records' => [],
                'workout_records' => [
                    'max_volume_workout' => null,
                    'longest_workout' => null,
                    'most_exercises_workout' => null,
                ]
            ]
        ]);
});

test('statistics endpoints require authentication', function () {
    // Создаем новый тест без аутентификации
    $this->refreshApplication();
    
    $endpoints = [
        '/api/v1/user/exercise-statistics',
        '/api/v1/user/time-analytics',
        '/api/v1/user/muscle-group-statistics',
        '/api/v1/user/records',
    ];
    
    foreach ($endpoints as $endpoint) {
        $response = $this->getJson($endpoint);
        $response->assertStatus(401);
    }
});