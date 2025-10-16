<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;

dataset('protected_endpoints', [
    'GET /api/v1/workouts' => ['GET', '/api/v1/workouts'],
    'POST /api/v1/workouts' => ['POST', '/api/v1/workouts', []],
    'GET /api/v1/workouts/{id}' => ['GET', '/api/v1/workouts/{id}'],
    'PUT /api/v1/workouts/{id}' => ['PUT', '/api/v1/workouts/{id}', []],
    'DELETE /api/v1/workouts/{id}' => ['DELETE', '/api/v1/workouts/{id}'],
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
    $this->workout = Workout::factory()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
        'started_at' => '2024-03-15 10:00:00',
        'finished_at' => '2024-03-15 11:30:00',
    ]);
});

describe('WorkoutController', function () {
    describe('GET /api/v1/workouts', function () {
        it('returns all workouts for authenticated user', function () {
            $workout1 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-10 10:00:00',
                'finished_at' => '2024-03-10 11:00:00',
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-20 10:00:00',
                'finished_at' => null,
            ]);
            
            // Create workout for another user (should not be returned)
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/workouts');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'started_at',
                            'finished_at',
                            'duration_minutes',
                            'exercise_count',
                            'total_volume',
                            'plan' => [
                                'id',
                                'name',
                            ],
                            'user' => [
                                'id',
                                'name',
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
                ]);

            expect($response->json('data'))->toHaveCount(3); // 2 new + 1 existing
        });

        it('supports search filtering by plan name', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);
            
            Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/workouts?search=Test');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(2); // Test Plan + existing workout
        });

        it('supports search filtering by user name', function () {
            $user1 = User::factory()->create(['name' => 'Test User']);
            $user2 = User::factory()->create(['name' => 'Another User']);
            
            Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $user1->id,
            ]);
            
            Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $user2->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/workouts?search=Test');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
        });

        it('supports plan filtering', function () {
            $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/workouts?plan_id={$plan1->id}");

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
        });

        it('supports completion filtering', function () {
            Workout::factory()->completed()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            Workout::factory()->inProgress()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/workouts?completed=true');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(2); // completed + existing completed
        });

        it('supports date range filtering', function () {
            Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-02-01 10:00:00',
            ]);
            
            Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-04-01 10:00:00',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/workouts?started_at_from=2024-03-01');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(2); // April workout + existing March workout
        });

        it('supports pagination', function () {
            for ($i = 0; $i < 25; $i++) {
                Workout::factory()->create([
                    'plan_id' => $this->plan->id,
                    'user_id' => $this->user->id,
                    'started_at' => now()->subDays($i),
                ]);
            }

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/workouts?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(10);
            expect($response->json('meta.per_page'))->toBe(10);
        });
    });

    describe('POST /api/v1/workouts', function () {
        it('creates a new workout', function () {
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 11:30:00',
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts', $data);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'started_at',
                        'finished_at',
                        'duration_minutes',
                        'exercise_count',
                        'total_volume',
                        'plan' => [
                            'id',
                            'name',
                        ],
                        'user' => [
                            'id',
                            'name',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.plan.id'))->toBe($this->plan->id);
            expect($response->json('data.user.id'))->toBe($this->user->id);

            $this->assertDatabaseHas('workouts', [
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 11:30:00',
            ]);
        });

        it('creates workout without finished_at', function () {
            $data = [
                'plan_id' => $this->plan->id,
                'started_at' => '2024-03-15 10:00:00',
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts', $data);

            $response->assertStatus(201);
            expect($response->json('data.finished_at'))->toBeNull();

            $this->assertDatabaseHas('workouts', [
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => null,
            ]);
        });

        it('validates required fields', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id', 'started_at']);
        });

        it('validates plan exists', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts', [
                    'plan_id' => 999,
                    'started_at' => '2024-03-15 10:00:00',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id']);
        });

        it('validates started_at is not in future', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts', [
                    'plan_id' => $this->plan->id,
                    'started_at' => now()->addDay()->format('Y-m-d H:i:s'),
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['started_at']);
        });

        it('validates finished_at is after started_at', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts', [
                    'plan_id' => $this->plan->id,
                    'started_at' => '2024-03-15 11:00:00',
                    'finished_at' => '2024-03-15 10:00:00',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['finished_at']);
        });
    });

    describe('GET /api/v1/workouts/{id}', function () {
        it('returns a specific workout', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/workouts/{$this->workout->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'started_at',
                        'finished_at',
                        'duration_minutes',
                        'exercise_count',
                        'total_volume',
                        'plan' => [
                            'id',
                            'name',
                        ],
                        'user' => [
                            'id',
                            'name',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.id'))->toBe($this->workout->id);
            expect($response->json('data.plan.id'))->toBe($this->plan->id);
        });

        it('returns 404 for non-existent workout', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/workouts/999');

            $response->assertStatus(404);
        });

        it('returns 404 for workout belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/workouts/{$otherWorkout->id}");

            $response->assertStatus(404);
        });

        it('returns exercises with history for workout', function () {
            // Создаем группу мышц
            $muscleGroup = \App\Models\MuscleGroup::factory()->create(['name' => 'Грудь']);
            
            // Создаем упражнение
            $exercise = \App\Models\Exercise::factory()->create([
                'name' => 'Жим лежа',
                'description' => 'Базовое упражнение для груди',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            // Создаем упражнение в плане
            $planExercise = \App\Models\PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $exercise->id,
                'order' => 1,
            ]);
            
            // Создаем несколько тренировок с подходами для истории
            $workout1 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-10 10:00:00',
                'finished_at' => '2024-03-10 11:00:00',
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-12 10:00:00',
                'finished_at' => '2024-03-12 11:00:00',
            ]);
            
            $workout3 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-14 10:00:00',
                'finished_at' => '2024-03-14 11:00:00',
            ]);
            
            // Создаем подходы для каждой тренировки
            \App\Models\WorkoutSet::factory()->create([
                'workout_id' => $workout1->id,
                'plan_exercise_id' => $planExercise->id,
                'weight' => 80.0,
                'reps' => 10,
            ]);
            
            \App\Models\WorkoutSet::factory()->create([
                'workout_id' => $workout2->id,
                'plan_exercise_id' => $planExercise->id,
                'weight' => 82.5,
                'reps' => 10,
            ]);
            
            \App\Models\WorkoutSet::factory()->create([
                'workout_id' => $workout3->id,
                'plan_exercise_id' => $planExercise->id,
                'weight' => 85.0,
                'reps' => 10,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/workouts/{$this->workout->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'started_at',
                        'finished_at',
                        'duration_minutes',
                        'exercise_count',
                        'total_volume',
                        'plan' => [
                            'id',
                            'name',
                        ],
                        'user' => [
                            'id',
                            'name',
                        ],
                        'exercises' => [
                            '*' => [
                                'id',
                                'order',
                                'exercise' => [
                                    'id',
                                    'name',
                                    'description',
                                    'muscle_group' => [
                                        'id',
                                        'name',
                                    ],
                                ],
                                'history' => [
                                    '*' => [
                                        'workout_id',
                                        'workout_date',
                                        'sets' => [
                                            '*' => [
                                                'id',
                                                'weight',
                                                'reps',
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            // Проверяем, что упражнения загружены
            $exercises = $response->json('data.exercises');
            expect($exercises)->toHaveCount(1);
            
            $exerciseData = $exercises[0];
            expect($exerciseData['exercise']['name'])->toBe('Жим лежа');
            expect($exerciseData['exercise']['muscle_group']['name'])->toBe('Грудь');
            
            // Проверяем историю (должна быть за последние 3 тренировки)
            expect($exerciseData['history'])->toHaveCount(3);
            
            // Проверяем, что история отсортирована по дате (новые сначала)
            $history = $exerciseData['history'];
            expect($history[0]['workout_date'])->toBe('2024-03-14T08:00:00.000000Z');
            expect($history[1]['workout_date'])->toBe('2024-03-12T08:00:00.000000Z');
            expect($history[2]['workout_date'])->toBe('2024-03-10T08:00:00.000000Z');
        });
    });

    describe('PUT /api/v1/workouts/{id}', function () {
        it('updates a workout', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/workouts/{$this->workout->id}", [
                    'plan_id' => $this->plan->id,
                    'started_at' => '2024-03-15 09:00:00',
                    'finished_at' => '2024-03-15 10:30:00',
                ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'started_at',
                        'finished_at',
                        'duration_minutes',
                        'exercise_count',
                        'total_volume',
                        'plan' => [
                            'id',
                            'name',
                        ],
                        'user' => [
                            'id',
                            'name',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.started_at'))->toBe('2024-03-15T06:00:00.000000Z');
            expect($response->json('data.finished_at'))->toBe('2024-03-15T07:30:00.000000Z');

            $this->assertDatabaseHas('workouts', [
                'id' => $this->workout->id,
                'started_at' => '2024-03-15 09:00:00',
                'finished_at' => '2024-03-15 10:30:00',
            ]);
        });

        it('validates required fields on update', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/workouts/{$this->workout->id}", [
                    'started_at' => '2024-03-15 09:00:00',
                    // plan_id отсутствует
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id']);
        });

        it('validates plan exists on update', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/workouts/{$this->workout->id}", [
                    'plan_id' => 999,
                    'started_at' => '2024-03-15 09:00:00',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id']);
        });

        it('validates finished_at is after started_at on update', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/workouts/{$this->workout->id}", [
                    'plan_id' => $this->plan->id,
                    'started_at' => '2024-03-15 11:00:00',
                    'finished_at' => '2024-03-15 10:00:00',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['finished_at']);
        });
    });

    describe('DELETE /api/v1/workouts/{id}', function () {
        it('deletes workout and related workout sets', function () {
            // Создаем WorkoutSet для этой тренировки
            $planExercise = \App\Models\PlanExercise::factory()->create([
                'plan_id' => $this->plan->id
            ]);
            
            $workoutSet = \App\Models\WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $planExercise->id,
                'weight' => 80.5,
                'reps' => 10
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/workouts/{$this->workout->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Тренировка успешно удалена'
                ]);
            
            $this->assertDatabaseMissing('workouts', [
                'id' => $this->workout->id,
            ]);
            $this->assertDatabaseMissing('workout_sets', [
                'id' => $workoutSet->id,
            ]);
        });

        it('returns 404 for non-existent workout', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/v1/workouts/999');

            $response->assertStatus(404);
        });

        it('returns 404 for workout belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/workouts/{$otherWorkout->id}");

            $response->assertStatus(404);
        });
    });

    describe('Authentication', function () {
        it('requires authentication for protected endpoints', function ($method, $url, $data = null) {
            // Заменяем {id} на реальный ID тренировки
            $url = str_replace('{id}', (string) $this->workout->id, $url);
            
            $response = match($method) {
                'GET' => $this->getJson($url),
                'POST' => $this->postJson($url, $data),
                'PUT' => $this->putJson($url, $data),
                'DELETE' => $this->deleteJson($url),
            };
            
            $response->assertStatus(401);
        })->with('protected_endpoints');
    });

    describe('POST /api/v1/workouts/start', function () {
        it('starts a new workout for a plan', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start', [
                    'plan_id' => $this->plan->id,
                ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'started_at',
                        'finished_at',
                        'plan' => [
                            'id',
                            'name',
                        ],
                        'user' => [
                            'id',
                            'name',
                        ],
                    ],
                    'message',
                ]);

            expect($response->json('data.started_at'))->not->toBeNull();
            expect($response->json('data.finished_at'))->toBeNull();
        });

        it('works without plan_id when active cycle exists', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start', []);

            $response->assertStatus(201);
            expect($response->json('data.plan.id'))->toBe($this->plan->id);
        });

        it('validates plan_id exists', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start', [
                    'plan_id' => 99999,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id']);
        });

        it('requires authentication', function () {
            $response = $this->postJson('/api/v1/workouts/start', [
                'plan_id' => $this->plan->id,
            ]);

            $response->assertStatus(401);
        });
    });

    describe('POST /api/v1/workouts/{workout}/finish', function () {
        it('finishes a started workout', function () {
            $workout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subHour(),
                'finished_at' => null,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/workouts/{$workout->id}/finish");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'started_at',
                        'finished_at',
                        'duration_minutes',
                    ],
                    'message',
                    'duration_minutes',
                ]);

            expect($response->json('data.finished_at'))->not->toBeNull();
            expect($response->json('duration_minutes'))->toBeInt();
        });

        it('cannot finish a workout that is not started', function () {
            $workout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => null,
                'finished_at' => null,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/workouts/{$workout->id}/finish");

            $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Нельзя завершить незапущенную тренировку',
                ]);
        });

        it('cannot finish an already finished workout', function () {
            $workout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subHours(2),
                'finished_at' => now()->subHour(),
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/workouts/{$workout->id}/finish");

            $response->assertStatus(422)
                ->assertJson([
                    'message' => 'Тренировка уже завершена',
                ]);
        });

        it('cannot finish workout of another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
                'started_at' => now()->subHour(),
                'finished_at' => null,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/workouts/{$otherWorkout->id}/finish");

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->postJson("/api/v1/workouts/{$this->workout->id}/finish");

            $response->assertStatus(401);
        });
    });

    describe('POST /api/v1/workouts/start - Auto Plan Detection', function () {
        beforeEach(function () {
            // Создаем дополнительные планы для тестирования логики
            $this->plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Plan 1',
                'order' => 1,
                'is_active' => true,
            ]);
            $this->plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Plan 2',
                'order' => 2,
                'is_active' => true,
            ]);
            $this->plan3 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Plan 3',
                'order' => 3,
                'is_active' => true,
            ]);
        });

        it('automatically determines first plan when no workouts completed', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(201);
            expect($response->json('data.plan.id'))->toBe($this->plan1->id);
        });

        it('automatically determines plan with least completed workouts', function () {
            // Завершаем 2 тренировки для первого плана
            Workout::factory()->create([
                'plan_id' => $this->plan1->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDays(2),
                'finished_at' => now()->subDays(2)->addHour(),
            ]);
            Workout::factory()->create([
                'plan_id' => $this->plan1->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDay(),
                'finished_at' => now()->subDay()->addHour(),
            ]);

            // Завершаем 1 тренировку для второго плана
            Workout::factory()->create([
                'plan_id' => $this->plan2->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDays(3),
                'finished_at' => now()->subDays(3)->addHour(),
            ]);

            // Третий план без завершенных тренировок

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(201);
            expect($response->json('data.plan.id'))->toBe($this->plan3->id);
        });

        it('chooses first plan when multiple plans have same completed count', function () {
            // Завершаем по 1 тренировке для каждого плана (включая план из основного beforeEach)
            Workout::factory()->create([
                'plan_id' => $this->plan->id, // план из основного beforeEach
                'user_id' => $this->user->id,
                'started_at' => now()->subDays(4),
                'finished_at' => now()->subDays(4)->addHour(),
            ]);
            Workout::factory()->create([
                'plan_id' => $this->plan1->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDays(3),
                'finished_at' => now()->subDays(3)->addHour(),
            ]);
            Workout::factory()->create([
                'plan_id' => $this->plan2->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDays(2),
                'finished_at' => now()->subDays(2)->addHour(),
            ]);
            Workout::factory()->create([
                'plan_id' => $this->plan3->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDay(),
                'finished_at' => now()->subDay()->addHour(),
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(201);
            // Должен выбрать план с наименьшим количеством завершенных тренировок
            // Поскольку у всех планов по 1 завершенной тренировке, выбирается первый по порядку
            expect($response->json('data.plan.id'))->toBe($this->plan1->id);
        });

        it('ignores inactive plans when determining next plan', function () {
            // Деактивируем первый план
            $this->plan1->update(['is_active' => false]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(201);
            expect($response->json('data.plan.id'))->toBe($this->plan2->id);
        });

        it('returns 404 when no active cycle with plans found', function () {
            // Удаляем все планы из цикла
            Plan::where('cycle_id', $this->cycle->id)->delete();

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Не найден активный цикл с планами'
                ]);
        });

        it('returns 404 when no active plans found', function () {
            // Деактивируем все планы
            Plan::where('cycle_id', $this->cycle->id)->update(['is_active' => false]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(404)
                ->assertJson([
                    'message' => 'Не найден активный цикл с планами'
                ]);
        });

        it('ignores incomplete workouts when counting completed workouts', function () {
            // Создаем незавершенную тренировку для первого плана
            Workout::factory()->create([
                'plan_id' => $this->plan1->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subHour(),
                'finished_at' => null, // Не завершена
            ]);

            // Завершаем тренировку для второго плана
            Workout::factory()->create([
                'plan_id' => $this->plan2->id,
                'user_id' => $this->user->id,
                'started_at' => now()->subDays(2),
                'finished_at' => now()->subDays(2)->addHour(),
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(201);
            // Должен выбрать первый план, так как у него 0 завершенных тренировок
            expect($response->json('data.plan.id'))->toBe($this->plan1->id);
        });

        it('uses latest cycle when multiple cycles exist', function () {
            // Создаем более новый цикл
            $newerCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'created_at' => now()->addDay(),
            ]);
            $newerPlan = Plan::factory()->create([
                'cycle_id' => $newerCycle->id,
                'name' => 'Newer Plan',
                'order' => 1,
                'is_active' => true,
            ]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start');

            $response->assertStatus(201);
            expect($response->json('data.plan.id'))->toBe($newerPlan->id);
        });

        it('still works with explicit plan_id when provided', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start', [
                    'plan_id' => $this->plan2->id,
                ]);

            $response->assertStatus(201);
            expect($response->json('data.plan.id'))->toBe($this->plan2->id);
        });
    });
});
