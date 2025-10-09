<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;

dataset('protected_endpoints', [
    'GET /api/workout-sets' => ['GET', '/api/workout-sets'],
    'POST /api/workout-sets' => ['POST', '/api/workout-sets', []],
    'GET /api/workout-sets/{id}' => ['GET', '/api/workout-sets/{id}'],
    'PUT /api/workout-sets/{id}' => ['PUT', '/api/workout-sets/{id}', []],
    'DELETE /api/workout-sets/{id}' => ['DELETE', '/api/workout-sets/{id}'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create([
        'user_id' => $this->user->id,
        'start_date' => '2024-03-01',
        'end_date' => '2024-03-31',
        'weeks' => 6,
    ]);
    $this->plan = Plan::factory()->create([
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
    ]);
    $this->muscleGroup = MuscleGroup::factory()->create();
    $this->exercise = Exercise::factory()->create(['muscle_group_id' => $this->muscleGroup->id]);
    $this->planExercise = PlanExercise::factory()->create([
        'plan_id' => $this->plan->id,
        'exercise_id' => $this->exercise->id,
    ]);
    $this->workout = Workout::factory()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
        'started_at' => '2024-03-15 10:00:00',
        'finished_at' => '2024-03-15 11:30:00',
    ]);
    $this->workoutSet = WorkoutSet::factory()->create([
        'workout_id' => $this->workout->id,
        'plan_exercise_id' => $this->planExercise->id,
        'weight' => 50.5,
        'reps' => 10,
    ]);
});

describe('WorkoutSetController', function () {
    describe('GET /api/workout-sets', function () {
        it('returns all workout sets for authenticated user', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 40.0,
                'reps' => 8,
            ]);
            
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 60.0,
                'reps' => 12,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/workout-sets?workout_id=' . $this->workout->id);
            
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'weight',
                            'reps',
                            'workout' => [
                                'id',
                                'started_at',
                                'finished_at',
                                'duration_minutes',
                                'exercise_count',
                                'total_volume',
                                'plan' => ['id', 'name'],
                                'user' => ['id', 'name'],
                            ],
                            'plan_exercise' => [
                                'id',
                                'order',
                                'exercise' => ['id', 'name', 'description'],
                            ],
                            'created_at',
                            'updated_at',
                        ]
                    ],
                    'message',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to',
                    ]
                ])
                ->assertJsonCount(3, 'data');
        });

        it('returns empty result when workout_id is not provided', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/workout-sets');
            
            $response->assertStatus(200)
                ->assertJsonCount(0, 'data')
                ->assertJson(['meta' => ['total' => 0]]);
        });

        it('filters workout sets by weight range', function () {
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 30.0,
            ]);
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 80.0,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/workout-sets?workout_id=' . $this->workout->id . '&weight_from=40&weight_to=70');
            
            $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.weight', '50.50');
        });

        it('filters workout sets by reps range', function () {
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 5,
            ]);
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 15,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/workout-sets?workout_id=' . $this->workout->id . '&reps_from=8&reps_to=12');
            
            $response->assertStatus(200)
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.reps', 10);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/workout-sets');
            
            $response->assertStatus(401);
        });
    });

    describe('POST /api/workout-sets', function () {
        it('creates workout set successfully', function () {
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 75.0,
                'reps' => 12,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'weight',
                        'reps',
                        'workout',
                        'plan_exercise',
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ])
                ->assertJsonPath('data.weight', '75.00')
                ->assertJsonPath('data.reps', 12);
            
            $this->assertDatabaseHas('workout_sets', [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 75.0,
                'reps' => 12,
            ]);
        });

        it('creates workout set with nullable fields', function () {
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => null,
                'reps' => null,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(201)
                ->assertJsonPath('data.weight', null)
                ->assertJsonPath('data.reps', null);
        });

        it('validates required fields', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', []);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['workout_id', 'plan_exercise_id']);
        });

        it('validates workout_id exists', function () {
            $data = [
                'workout_id' => 999999,
                'plan_exercise_id' => $this->planExercise->id,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['workout_id']);
        });

        it('validates plan_exercise_id exists', function () {
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => 999999,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_exercise_id']);
        });

        it('validates workout belongs to user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            
            $data = [
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['workout_id']);
        });

        it('validates plan_exercise belongs to same plan as workout', function () {
            $otherPlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $otherPlan->id,
                'exercise_id' => $this->exercise->id,
            ]);
            
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $otherPlanExercise->id,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_exercise_id']);
        });

        it('validates weight range', function () {
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => -10.0,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['weight']);
        });

        it('validates reps range', function () {
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => -5,
            ];
            
            $response = $this->actingAs($this->user)
                ->postJson('/api/workout-sets', $data);
            
            $response->assertStatus(422)
                ->assertJsonValidationErrors(['reps']);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/workout-sets', []);
            
            $response->assertStatus(401);
        });
    });

    describe('GET /api/workout-sets/{id}', function () {
        it('returns specific workout set', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/workout-sets/' . $this->workoutSet->id);
            
            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'weight',
                        'reps',
                        'workout',
                        'plan_exercise',
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ])
                ->assertJsonPath('data.id', $this->workoutSet->id)
                ->assertJsonPath('data.weight', '50.50')
                ->assertJsonPath('data.reps', 10);
        });

        it('returns 404 for non-existent workout set', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/workout-sets/999999');
            
            $response->assertStatus(404);
        });

        it('returns 404 for workout set from other user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            $otherWorkoutSet = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/workout-sets/' . $otherWorkoutSet->id);
            
            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson('/api/workout-sets/' . $this->workoutSet->id);
            
            $response->assertStatus(401);
        });
    });

    describe('PUT /api/workout-sets/{id}', function () {
        it('updates workout set successfully', function () {
            $data = [
                'weight' => 60.0,
                'reps' => 15,
            ];
            
            $response = $this->actingAs($this->user)
                ->putJson('/api/workout-sets/' . $this->workoutSet->id, $data);
            
            $response->assertStatus(200)
                ->assertJsonPath('data.weight', '60.00')
                ->assertJsonPath('data.reps', 15);
            
            $this->assertDatabaseHas('workout_sets', [
                'id' => $this->workoutSet->id,
                'weight' => 60.0,
                'reps' => 15,
            ]);
        });

        it('returns 404 for non-existent workout set', function () {
            $response = $this->actingAs($this->user)
                ->putJson('/api/workout-sets/999999', ['weight' => 100.0]);
            
            $response->assertStatus(404);
        });

        it('returns 404 for workout set from other user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            $otherWorkoutSet = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $data = [
                'weight' => 100.0,
            ];
            
            $response = $this->actingAs($this->user)
                ->putJson('/api/workout-sets/' . $otherWorkoutSet->id, $data);
            
            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->putJson('/api/workout-sets/' . $this->workoutSet->id, []);
            
            $response->assertStatus(401);
        });
    });

    describe('DELETE /api/workout-sets/{id}', function () {
        it('deletes workout set successfully', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/workout-sets/' . $this->workoutSet->id);
            
            $response->assertStatus(200)
                ->assertJsonPath('data', null)
                ->assertJsonPath('message', 'Подход успешно удален');
            
            $this->assertDatabaseMissing('workout_sets', [
                'id' => $this->workoutSet->id,
            ]);
        });

        it('returns 404 for non-existent workout set', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/workout-sets/999999');
            
            $response->assertStatus(404);
        });

        it('returns 404 for workout set from other user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            $otherWorkoutSet = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/workout-sets/' . $otherWorkoutSet->id);
            
            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->deleteJson('/api/workout-sets/' . $this->workoutSet->id);
            
            $response->assertStatus(401);
        });
    });

    describe('GET /api/workouts/{workoutId}/workout-sets', function () {
        it('returns workout sets for specific workout', function () {
            WorkoutSet::factory()->count(2)->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/workouts/' . $this->workout->id . '/workout-sets');
            
            $response->assertStatus(200)
                ->assertJsonCount(3, 'data') // 2 new + 1 existing
                ->assertJsonPath('message', 'Подходы тренировки успешно получены');
        });

        it('returns 404 for workout from other user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/workouts/' . $otherWorkout->id . '/workout-sets');
            
            $response->assertStatus(404);
        });
    });

    describe('GET /api/plan-exercises/{planExerciseId}/workout-sets', function () {
        it('returns workout sets for specific plan exercise', function () {
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            WorkoutSet::factory()->count(2)->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/plan-exercises/' . $this->planExercise->id . '/workout-sets');
            
            $response->assertStatus(200)
                ->assertJsonCount(3, 'data') // 2 new + 1 existing
                ->assertJsonPath('message', 'Подходы упражнения успешно получены');
        });

        it('returns 404 for plan exercise from other user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $otherPlan->id,
                'exercise_id' => $this->exercise->id,
            ]);
            
            $response = $this->actingAs($this->user)
                ->getJson('/api/plan-exercises/' . $otherPlanExercise->id . '/workout-sets');
            
            $response->assertStatus(404);
        });
    });

    describe('Authentication', function () {
        it('requires authentication for all protected endpoints', function ($method, $url, $data = []) {
            $url = str_replace('{id}', (string)$this->workoutSet->id, $url);
            
            $response = $this->json($method, $url, $data);
            
            $response->assertStatus(401);
        })->with('protected_endpoints');
    });
});
