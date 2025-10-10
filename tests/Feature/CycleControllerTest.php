<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\User;

dataset('protected_endpoints', [
    'GET /api/v1/cycles' => ['GET', '/api/v1/cycles'],
    'POST /api/v1/cycles' => ['POST', '/api/v1/cycles', []],
    'GET /api/v1/cycles/{id}' => ['GET', '/api/v1/cycles/{id}'],
    'PUT /api/v1/cycles/{id}' => ['PUT', '/api/v1/cycles/{id}', []],
    'DELETE /api/v1/cycles/{id}' => ['DELETE', '/api/v1/cycles/{id}'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create([
        'user_id' => $this->user->id,
        'start_date' => '2024-03-01',
        'end_date' => '2024-03-31',
        'weeks' => 6, // Устанавливаем конкретное количество недель
    ]);
});

describe('CycleController', function () {
    describe('GET /api/cycles', function () {
        it('returns all cycles for authenticated user', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Cycle 1',
                'weeks' => 4,
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Cycle 2',
                'weeks' => 8,
            ]);
            
            // Create cycle for another user (should not be returned)
            $otherUser = User::factory()->create();
            Cycle::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/cycles');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'user' => [
                                'id',
                                'name',
                            ],
                            'start_date',
                            'end_date',
                            'weeks',
                            'progress_percentage',
                            'completed_workouts_count',
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
            Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Cycle',
            ]);
            
            Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Another Cycle',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/cycles?search=Test');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('Test Cycle');
        });

        it('supports pagination', function () {
            // Создаем циклы с разными датами, чтобы избежать division by zero
            for ($i = 0; $i < 25; $i++) {
                Cycle::factory()->create([
                    'user_id' => $this->user->id,
                    'start_date' => now()->subDays($i * 2),
                    'end_date' => now()->addDays($i * 2 + 7),
                ]);
            }

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/cycles?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(10);
            expect($response->json('meta.per_page'))->toBe(10);
        });

        it('supports date range filtering', function () {
            $oldCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ]);
            
            $recentCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-06-01',
                'end_date' => '2024-06-30',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/cycles?start_date_from=2024-05-01');

            $response->assertStatus(200);
            $data = $response->json('data');
            
            // Проверяем, что возвращается только цикл с датой >= 2024-05-01
            expect($data)->toHaveCount(1);
            
            // Проверяем, что это именно тот цикл, который мы ожидаем
            $cycleIds = collect($data)->pluck('id')->toArray();
            expect($cycleIds)->toContain($recentCycle->id);
            expect($cycleIds)->not->toContain($oldCycle->id);
            expect($cycleIds)->not->toContain($this->cycle->id);
        });

        it('supports weeks filtering', function () {
            Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 4,
            ]);
            
            Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 8,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/cycles?weeks=4');

            $response->assertStatus(200);
            $data = $response->json('data');
            
            // Проверяем, что возвращается только цикл с 4 неделями
            expect($data)->toHaveCount(1);
            
            // Проверяем, что это именно тот цикл, который мы ожидаем
            $cycleIds = collect($data)->pluck('id')->toArray();
            expect($cycleIds)->not->toContain($this->cycle->id);
        });
    });

    describe('POST /api/cycles', function () {
        it('creates a new cycle', function () {
            $data = [
                'name' => 'New Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', $data);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'start_date',
                        'end_date',
                        'weeks',
                        'progress_percentage',
                        'completed_workouts_count',
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('New Cycle');
            expect($response->json('data.weeks'))->toBe(4);

            $this->assertDatabaseHas('cycles', [
                'user_id' => $this->user->id,
                'name' => 'New Cycle',
                'weeks' => 4,
            ]);
        });

        it('creates cycle with minimal required data', function () {
            $data = [
                'name' => 'Minimal Cycle',
                'weeks' => 6,
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', $data);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'start_date',
                        'end_date',
                        'weeks',
                        'progress_percentage',
                        'completed_workouts_count',
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('Minimal Cycle');
            expect($response->json('data.weeks'))->toBe(6);
            expect($response->json('data.start_date'))->toBeNull();
            expect($response->json('data.end_date'))->toBeNull();

            $this->assertDatabaseHas('cycles', [
                'user_id' => $this->user->id,
                'name' => 'Minimal Cycle',
                'weeks' => 6,
                'start_date' => null,
                'end_date' => null,
            ]);
        });

        it('validates required fields', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'weeks']);
        });

        it('validates unique name per user', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => $this->cycle->name,
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 4,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for different users', function () {
            $otherUser = User::factory()->create();

            $response = $this->actingAs($otherUser)
                ->postJson('/api/v1/cycles', [
                    'name' => $this->cycle->name,
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 4,
                ]);

            $response->assertStatus(201);
        });

        it('validates date relationships', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Test Cycle',
                    'start_date' => '2024-01-31',
                    'end_date' => '2024-01-01',
                    'weeks' => 4,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['start_date']);
        });

        it('validates weeks range', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Test Cycle',
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 53,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['weeks']);
        });

        it('validates minimum weeks', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/cycles', [
                    'name' => 'Test Cycle',
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 0,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['weeks']);
        });
    });

    describe('GET /api/cycles/{id}', function () {
        it('returns a specific cycle', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/cycles/{$this->cycle->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'start_date',
                        'end_date',
                        'weeks',
                        'progress_percentage',
                        'completed_workouts_count',
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.id'))->toBe($this->cycle->id);
            expect($response->json('data.name'))->toBe($this->cycle->name);
        });

        it('returns 404 for non-existent cycle', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/cycles/999');

            $response->assertStatus(404);
        });

        it('returns 404 for cycle belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/cycles/{$otherCycle->id}");

            $response->assertStatus(404);
        });
    });

    describe('PUT /api/cycles/{id}', function () {
        it('updates a cycle', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/cycles/{$this->cycle->id}", [
                    'name' => 'Updated Cycle',
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 8,
                ]);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'start_date',
                        'end_date',
                        'weeks',
                        'progress_percentage',
                        'completed_workouts_count',
                        'created_at',
                        'updated_at',
                    ],
                    'message'
                ]);

            expect($response->json('data.name'))->toBe('Updated Cycle');
            expect($response->json('data.weeks'))->toBe(8);

            $this->assertDatabaseHas('cycles', [
                'id' => $this->cycle->id,
                'name' => 'Updated Cycle',
                'weeks' => 8,
            ]);
        });

        it('validates unique name on update', function () {
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Another Cycle',
            ]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/cycles/{$cycle2->id}", [
                    'name' => $this->cycle->name,
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 4,
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });

        it('allows same name for same cycle on update', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/cycles/{$this->cycle->id}", [
                    'name' => $this->cycle->name,
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 4,
                ]);

            $response->assertStatus(200);
        });

        it('validates required fields on update', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/cycles/{$this->cycle->id}", [
                    'start_date' => '2024-01-01',
                    'end_date' => '2024-01-31',
                    'weeks' => 4,
                    // name отсутствует
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
        });
    });

    describe('DELETE /api/cycles/{id}', function () {
        it('deletes a cycle', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/cycles/{$this->cycle->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Цикл успешно удален'
                ]);

            $this->assertDatabaseMissing('cycles', [
                'id' => $this->cycle->id,
            ]);
        });

        it('returns 404 for non-existent cycle', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/cycles/999');

            $response->assertStatus(404);
        });

        it('returns 404 for cycle belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/cycles/{$otherCycle->id}");

            $response->assertStatus(404);
        });
    });

    describe('Authentication', function () {
        it('requires authentication for protected endpoints', function ($method, $url, $data = null) {
            // Заменяем {id} на реальный ID цикла
            $url = str_replace('{id}', (string) $this->cycle->id, $url);
            
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
