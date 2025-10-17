<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

dataset('protected_endpoints', [
    'GET /api/exercises' => ['GET', '/api/v1/exercises'],
    'POST /api/exercises' => ['POST', '/api/v1/exercises', []],
    'GET /api/exercises/{id}' => ['GET', '/api/exercises/{id}'],
    'PUT /api/exercises/{id}' => ['PUT', '/api/exercises/{id}', []],
    'DELETE /api/exercises/{id}' => ['DELETE', '/api/exercises/{id}'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

describe('ExerciseController', function () {
    describe('GET /api/exercises', function () {
        it('returns all exercises for authenticated user', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise1 = Exercise::factory()->create([
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            $exercise2 = Exercise::factory()->create([
                'name' => 'Bench Press',
                'description' => 'Bench press exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            // Create exercise for another user (should not be returned)
            Exercise::factory()->create([
                'name' => 'Other User Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => User::factory()->create()->id,
            ]);

            $response = $this->getJson('/api/v1/exercises');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'description',
                            'user_id',
                            'is_active',
                            'created_at',
                            'updated_at',
                            'muscle_group',
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

            expect($response->json('data'))->toHaveCount(2);
        });

        it('supports search filtering', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Bench Press',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->getJson('/api/v1/exercises?search=push');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('Push-ups');
        });

        it('supports pagination', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            Exercise::factory()->count(25)->create([
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->getJson('/api/v1/exercises?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(10);
            expect($response->json('meta.per_page'))->toBe(10);
        });

        it('supports is_active filtering', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Active Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Inactive Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => false,
            ]);

            // Тест фильтра активных упражнений
            $response = $this->getJson('/api/v1/exercises?is_active=1');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('Active Exercise');

            // Тест фильтра неактивных упражнений
            $response = $this->getJson('/api/v1/exercises?is_active=0');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('Inactive Exercise');

            // Тест без фильтра (все упражнения)
            $response = $this->getJson('/api/v1/exercises');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(2);
        });
    });

    describe('POST /api/exercises', function () {
        it('creates a new exercise', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $data = [
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'is_active' => true,
            ];

            $response = $this->postJson('/api/v1/exercises', $data);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'user_id',
                        'is_active',
                        'created_at',
                        'updated_at',
                        'muscle_group',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('Push-ups');
            expect($response->json('data.user_id'))->toBe($this->user->id);

            $this->assertDatabaseHas('exercises', [
                'name' => 'Push-ups',
                'user_id' => $this->user->id,
            ]);
        });

        it('validates required fields', function () {
            $response = $this->postJson('/api/v1/exercises', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'muscle_group_id']);
        });

        it('validates unique name per user', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->postJson('/api/v1/exercises', [
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for different users', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $otherUser = User::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $otherUser->id,
            ]);

            $response = $this->postJson('/api/v1/exercises', [
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
            ]);

            $response->assertStatus(201);
        });
    });

    describe('GET /api/exercises/{id}', function () {
        it('returns a specific exercise', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->getJson("/api/v1/exercises/{$exercise->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'user_id',
                        'is_active',
                        'created_at',
                        'updated_at',
                        'muscle_group',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('Push-ups');
        });

        it('returns 404 for non-existent exercise', function () {
            $response = $this->getJson('/api/exercises/999');

            $response->assertStatus(404);
        });

        it('returns 404 for exercise belonging to another user', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $otherUser = User::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $otherUser->id,
            ]);

            $response = $this->getJson("/api/v1/exercises/{$exercise->id}");

            $response->assertStatus(404);
        });
    });

    describe('PUT /api/exercises/{id}', function () {
        it('updates an exercise', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->putJson("/api/v1/exercises/{$exercise->id}", [
                'name' => 'Push-ups Updated',
                'description' => 'Updated description',
                'muscle_group_id' => $muscleGroup->id,
                'is_active' => false,
            ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'description',
                        'user_id',
                        'is_active',
                        'created_at',
                        'updated_at',
                        'muscle_group',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('Push-ups Updated');
            expect($response->json('data.is_active'))->toBeFalse();
            
            $this->assertDatabaseHas('exercises', [
                'id' => $exercise->id,
                'name' => 'Push-ups Updated',
                'is_active' => false,
            ]);
        });

        it('validates unique name on update', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise1 = Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            $exercise2 = Exercise::factory()->create([
                'name' => 'Bench Press',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->putJson("/api/v1/exercises/{$exercise2->id}", [
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });
    });

    describe('DELETE /api/exercises/{id}', function () {
        it('deletes an exercise', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $response = $this->deleteJson("/api/v1/exercises/{$exercise->id}");

            $response->assertStatus(200)
                ->assertJsonStructure(['data', 'message']);

            $this->assertDatabaseMissing('exercises', [
                'id' => $exercise->id,
            ]);
        });

        it('returns 404 for non-existent exercise', function () {
            $response = $this->deleteJson('/api/exercises/999');

            $response->assertStatus(404);
        });

        it('returns 404 for exercise belonging to another user', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $otherUser = User::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $otherUser->id,
            ]);

            $response = $this->deleteJson("/api/v1/exercises/{$exercise->id}");

            $response->assertStatus(404);
        });
    });
});
