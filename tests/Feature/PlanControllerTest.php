<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;

dataset('protected_endpoints', [
    'GET /api/v1/plans' => ['GET', '/api/v1/plans'],
    'POST /api/v1/plans' => ['POST', '/api/v1/plans', []],
    'GET /api/v1/plans/{id}' => ['GET', '/api/v1/plans/{id}'],
    'PUT /api/v1/plans/{id}' => ['PUT', '/api/v1/plans/{id}', []],
    'DELETE /api/v1/plans/{id}' => ['DELETE', '/api/v1/plans/{id}'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
});

describe('PlanController', function () {
    describe('GET /api/plans', function () {
        it('returns all plans for authenticated user', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Plan 1',
                'order' => 1,
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Plan 2',
                'order' => 2,
            ]);
            
            // Create plan for another user (should not be returned)
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/plans');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'order',
                            'exercise_count',
                            'cycle' => [
                                'id',
                                'name',
                            ],
                            'exercises' => [
                                '*' => [
                                    'id',
                                    'name',
                                ],
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

        it('supports search filtering', function () {
            Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            
            Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/plans?search=Test');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('Test Plan');
        });

        it('supports pagination', function () {
            Plan::factory()->count(25)->create(['cycle_id' => $this->cycle->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/plans?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(10);
            expect($response->json('meta.per_page'))->toBe(10);
        });

        it('supports cycle filtering', function () {
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/plans?cycle_id={$this->cycle->id}");

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe($this->plan->id);
        });

        it('supports standalone filtering', function () {
            $standalonePlan = Plan::factory()->create([
                'cycle_id' => null,
                'user_id' => $this->user->id,
            ]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);

            // Тест фильтрации standalone планов
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/plans?standalone=true');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe($standalonePlan->id);
            expect($response->json('data.0.cycle'))->toBeNull();

            // Тест фильтрации планов с циклом
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/plans?standalone=false');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(2); // $this->plan + $cyclePlan
            expect($response->json('data.0.cycle'))->not->toBeNull();
            expect($response->json('data.1.cycle'))->not->toBeNull();
        });
    });

    describe('POST /api/plans', function () {
        it('creates a new plan', function () {
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'New Plan',
                'order' => 1,
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', $data);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'order',
                        'exercise_count',
                        'cycle' => [
                            'id',
                            'name',
                        ],
                        'exercises' => [
                            '*' => [
                                'id',
                                'name',
                            ],
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('New Plan');
            expect($response->json('data.order'))->toBe(1);

            $this->assertDatabaseHas('plans', [
                'cycle_id' => $this->cycle->id,
                'name' => 'New Plan',
                'order' => 1,
            ]);
        });

        it('validates required fields', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('validates unique name per cycle', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => $this->plan->name,
                    'order' => 1,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for different cycles', function () {
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $otherCycle->id,
                    'name' => $this->plan->name,
                    'order' => 1,
                ]);

            $response->assertStatus(201);
        });

        it('validates cycle_id exists', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => 999999,
                    'name' => 'Test Plan',
                    'order' => 1,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cycle_id']);
        });

        it('creates a plan without cycle_id', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'name' => 'Standalone Plan',
                ]);

            $response->assertStatus(201);

            $responseData = $response->json('data');
            expect($responseData['cycle'])->toBeNull();

            $this->assertDatabaseHas('plans', [
                'id' => $responseData['id'],
                'cycle_id' => null,
                'name' => 'Standalone Plan',
            ]);
        });

        it('validates positive order', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'Test Plan',
                    'order' => 0,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['order']);
        });

        it('can create plan without order', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'Plan Without Order',
                ]);

            $response->assertStatus(201)
                ->assertJson([
                    'data' => [
                        'name' => 'Plan Without Order',
                        'order' => null,
                    ],
                    'message' => 'План успешно создан'
                ]);
        });
    });

    describe('GET /api/plans/{id}', function () {
        it('returns a specific plan', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/plans/{$this->plan->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'order',
                        'exercise_count',
                        'cycle' => [
                            'id',
                            'name',
                        ],
                        'exercises' => [
                            '*' => [
                                'id',
                                'name',
                            ],
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.id'))->toBe($this->plan->id);
            expect($response->json('data.name'))->toBe($this->plan->name);
        });

        it('returns 404 for non-existent plan', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/plans/999');

            $response->assertStatus(404);
        });

        it('returns 404 for plan belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/plans/{$otherPlan->id}");

            $response->assertStatus(404);
        });
    });

    describe('PUT /api/plans/{id}', function () {
        it('updates a plan', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$this->plan->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'Updated Plan',
                    'order' => 2,
                ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'order',
                        'exercise_count',
                        'cycle' => [
                            'id',
                            'name',
                        ],
                        'exercises' => [
                            '*' => [
                                'id',
                                'name',
                            ],
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('Updated Plan');
            expect($response->json('data.order'))->toBe(2);

            $this->assertDatabaseHas('plans', [
                'id' => $this->plan->id,
                'name' => 'Updated Plan',
                'order' => 2,
            ]);
        });

        it('validates unique name on update', function () {
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$plan2->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => $this->plan->name,
                    'order' => 1,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for same plan on update', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$this->plan->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => $this->plan->name,
                    'order' => 1,
                ]);

            $response->assertStatus(200);
        });
    });

    describe('DELETE /api/plans/{id}', function () {
        it('deletes a plan', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/plans/{$this->plan->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'План успешно удален'
                ]);

            $this->assertDatabaseMissing('plans', [
                'id' => $this->plan->id,
            ]);
        });

        it('returns 404 for non-existent plan', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/plans/999');

            $response->assertStatus(404);
        });

        it('returns 404 for plan belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/plans/{$otherPlan->id}");

            $response->assertStatus(404);
        });
    });

    describe('POST /api/plans/{id}/duplicate', function () {
        beforeEach(function () {
            // Создаем план с упражнениями для тестирования копирования
            $this->planWithExercises = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Original Plan',
                'order' => 1,
                'is_active' => true,
            ]);

            // Создаем упражнения для плана
            $this->exercise1 = \App\Models\Exercise::factory()->create();
            $this->exercise2 = \App\Models\Exercise::factory()->create();

            \App\Models\PlanExercise::factory()->create([
                'plan_id' => $this->planWithExercises->id,
                'exercise_id' => $this->exercise1->id,
                'order' => 1,
            ]);

            \App\Models\PlanExercise::factory()->create([
                'plan_id' => $this->planWithExercises->id,
                'exercise_id' => $this->exercise2->id,
                'order' => 2,
            ]);

            // Создаем новый цикл для копирования
            $this->newCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
        });

        it('duplicates a plan with exercises to another cycle', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", [
                    'cycle_id' => $this->newCycle->id,
                ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'order',
                        'is_active',
                        'exercise_count',
                        'cycle' => [
                            'id',
                            'name',
                        ],
                    ],
                    'message'
                ]);

            $responseData = $response->json('data');
            expect($responseData['cycle']['id'])->toBe($this->newCycle->id);
            expect($responseData['name'])->toBe('Original Plan (копия)');
            expect($responseData['exercise_count'])->toBe(2);

            // Проверяем, что план создан в базе данных
            $this->assertDatabaseHas('plans', [
                'id' => $responseData['id'],
                'cycle_id' => $this->newCycle->id,
                'name' => 'Original Plan (копия)',
                'order' => 1,
                'is_active' => true,
            ]);

            // Проверяем, что упражнения скопированы
            $newPlanExercises = \App\Models\PlanExercise::where('plan_id', $responseData['id'])->get();
            expect($newPlanExercises)->toHaveCount(2);
            expect($newPlanExercises->pluck('exercise_id')->toArray())->toBe([$this->exercise1->id, $this->exercise2->id]);
        });

        it('duplicates a plan without cycle_id (creates standalone plan)', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", []);

            $response->assertStatus(201);

            $responseData = $response->json('data');
            expect($responseData['cycle'])->toBeNull();
            expect($responseData['name'])->toBe('Original Plan (копия)');
            expect($responseData['exercise_count'])->toBe(2);

            // Проверяем, что план создан в базе данных
            $this->assertDatabaseHas('plans', [
                'id' => $responseData['id'],
                'cycle_id' => null,
                'name' => 'Original Plan (копия)',
                'order' => 1,
                'is_active' => true,
            ]);
        });

        it('duplicates a plan with custom name', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", [
                    'cycle_id' => $this->newCycle->id,
                    'name' => 'Custom Copy Name',
                ]);

            $response->assertStatus(201);

            $responseData = $response->json('data');
            expect($responseData['name'])->toBe('Custom Copy Name');

            $this->assertDatabaseHas('plans', [
                'id' => $responseData['id'],
                'name' => 'Custom Copy Name',
            ]);
        });

        it('validates cycle_id exists when provided', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", [
                    'cycle_id' => 999,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cycle_id']);
        });

        it('validates name uniqueness within cycle', function () {
            // Создаем план с таким же именем в новом цикле
            Plan::factory()->create([
                'cycle_id' => $this->newCycle->id,
                'name' => 'Duplicate Name',
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", [
                    'cycle_id' => $this->newCycle->id,
                    'name' => 'Duplicate Name',
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for plans in different cycles or standalone', function () {
            // Создаем план без цикла с таким же именем
            Plan::factory()->create([
                'cycle_id' => null,
                'name' => 'Duplicate Name',
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", [
                    'cycle_id' => $this->newCycle->id,
                    'name' => 'Duplicate Name',
                ]);

            $response->assertStatus(201);
        });

        it('returns 404 for non-existent plan', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans/999/duplicate', [
                    'cycle_id' => $this->newCycle->id,
                ]);

            $response->assertStatus(404);
        });

        it('returns 404 for plan belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$otherPlan->id}/duplicate", [
                    'cycle_id' => $this->newCycle->id,
                ]);

            $response->assertStatus(404);
        });

        it('returns 404 for cycle belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", [
                    'cycle_id' => $otherCycle->id,
                ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->postJson("/api/v1/plans/{$this->planWithExercises->id}/duplicate", [
                'cycle_id' => $this->newCycle->id,
            ]);

            $response->assertStatus(401);
        });

        it('can access standalone plans (plans without cycle)', function () {
            // Создаем план без цикла
            $standalonePlan = Plan::factory()->create([
                'cycle_id' => null,
                'user_id' => $this->user->id,
                'name' => 'Standalone Plan',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/plans/{$standalonePlan->id}");

            $response->assertStatus(200);
            expect($response->json('data.cycle'))->toBeNull();
        });
    });

    describe('Authentication', function () {
        it('requires authentication for protected endpoints', function ($method, $url, $data = null) {
            // Заменяем {id} на реальный ID плана
            $url = str_replace('{id}', (string) $this->plan->id, $url);
            
            $response = match($method) {
                'GET' => $this->getJson($url),
                'POST' => $this->postJson($url, $data),
                'PUT' => $this->putJson($url, $data),
                'DELETE' => $this->deleteJson($url),
            };
            
            $response->assertStatus(401);
        })->with('protected_endpoints');
    });
});
