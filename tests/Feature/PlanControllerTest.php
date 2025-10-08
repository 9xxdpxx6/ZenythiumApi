<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;

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
                ->getJson('/api/plans');

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
                ->getJson('/api/plans?search=Test');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('Test Plan');
        });

        it('supports pagination', function () {
            Plan::factory()->count(25)->create(['cycle_id' => $this->cycle->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/plans?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(10);
            expect($response->json('meta.per_page'))->toBe(10);
        });

        it('supports cycle filtering', function () {
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/plans?cycle_id={$this->cycle->id}");

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.id'))->toBe($this->plan->id);
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
                ->postJson('/api/plans', $data);

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
                ->postJson('/api/plans', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cycle_id', 'name']);
        });

        it('validates unique name per cycle', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/plans', [
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
                ->postJson('/api/plans', [
                    'cycle_id' => $otherCycle->id,
                    'name' => $this->plan->name,
                    'order' => 1,
                ]);

            $response->assertStatus(201);
        });

        it('validates cycle_id exists', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/plans', [
                    'cycle_id' => 999999,
                    'name' => 'Test Plan',
                    'order' => 1,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['cycle_id']);
        });

        it('validates positive order', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/plans', [
                    'cycle_id' => $this->cycle->id,
                    'name' => 'Test Plan',
                    'order' => 0,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['order']);
        });

        it('can create plan without order', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/plans', [
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
                ->getJson("/api/plans/{$this->plan->id}");

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
                ->putJson("/api/plans/{$this->plan->id}", [
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
                ->putJson("/api/plans/{$plan2->id}", [
                    'cycle_id' => $this->cycle->id,
                    'name' => $this->plan->name,
                    'order' => 1,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for same plan on update', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/plans/{$this->plan->id}", [
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
                ->deleteJson("/api/plans/{$this->plan->id}");

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

    describe('Authentication', function () {
        it('requires authentication for all endpoints', function () {
            $response = $this->getJson('/api/plans');
            $response->assertStatus(401);

            $response = $this->postJson('/api/plans', []);
            $response->assertStatus(401);

            $response = $this->getJson("/api/plans/{$this->plan->id}");
            $response->assertStatus(401);

            $response = $this->putJson("/api/plans/{$this->plan->id}", []);
            $response->assertStatus(401);

            $response = $this->deleteJson("/api/plans/{$this->plan->id}");
            $response->assertStatus(401);
        });
    });
});
