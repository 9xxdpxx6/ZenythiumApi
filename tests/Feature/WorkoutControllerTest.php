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
        it('deletes a workout', function () {
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

        it('validates plan_id is required', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/workouts/start', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['plan_id']);
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
});
