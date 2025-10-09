<?php

declare(strict_types=1);

use App\Models\Metric;
use App\Models\User;

dataset('protected_endpoints', [
    'GET /api/metrics' => ['GET', '/api/metrics'],
    'POST /api/metrics' => ['POST', '/api/metrics', []],
    'GET /api/metrics/{id}' => ['GET', '/api/metrics/{id}'],
    'PUT /api/metrics/{id}' => ['PUT', '/api/metrics/{id}', []],
    'DELETE /api/metrics/{id}' => ['DELETE', '/api/metrics/{id}'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->metric = Metric::factory()->create([
        'user_id' => $this->user->id,
        'date' => '2024-03-15',
        'weight' => 75.5,
        'note' => 'Test metric',
    ]);
});

describe('MetricController', function () {
    describe('GET /api/metrics', function () {
        it('returns all metrics for authenticated user', function () {
            $metric1 = Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-10',
                'weight' => 76.0,
                'note' => 'First metric',
            ]);
            
            $metric2 = Metric::factory()->create([
                'user_id' => $this->user->id,
                'date' => '2024-03-20',
                'weight' => 75.0,
                'note' => 'Second metric',
            ]);

            $response = $this->actingAs($this->user)
                ->getJson('/api/metrics');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'date',
                            'weight',
                            'note',
                            'user' => [
                                'id',
                                'name',
                            ],
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'message',
                    'meta' => [
                        'current_page',
                        'last_page',
                        'per_page',
                        'total',
                        'from',
                        'to',
                    ],
                ]);

            expect($response->json('data'))->toHaveCount(3);
        });

        it('returns empty result for unauthenticated user', function () {
            $response = $this->getJson('/api/metrics');

            $response->assertStatus(401);
        });

        it('filters metrics by date range', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/metrics?date_from=2024-03-10&date_to=2024-03-20');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
        });

        it('filters metrics by weight range', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/metrics?weight_from=75&weight_to=77');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
        });

        it('searches metrics by note', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/metrics?search=Test');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
        });
    });

    describe('POST /api/metrics', function () {
        it('creates a new metric', function () {
            $data = [
                'date' => '2024-03-25',
                'weight' => 74.5,
                'note' => 'New metric',
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/metrics', $data);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'date',
                        'weight',
                        'note',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message',
                ]);

            expect($response->json('data.weight'))->toBe('74.50');
            expect($response->json('data.note'))->toBe('New metric');

            $this->assertDatabaseHas('metrics', [
                'user_id' => $this->user->id,
                'date' => '2024-03-25 00:00:00',
                'weight' => 74.5,
                'note' => 'New metric',
            ]);
        });

        it('validates required fields', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/metrics', []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['date', 'weight']);
        });

        it('validates date format', function () {
            $data = [
                'date' => 'invalid-date',
                'weight' => 75.0,
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/metrics', $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
        });

        it('validates weight is numeric', function () {
            $data = [
                'date' => '2024-03-25',
                'weight' => 'not-a-number',
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/metrics', $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['weight']);
        });

        it('validates weight range', function () {
            $data = [
                'date' => '2024-03-25',
                'weight' => -10,
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/metrics', $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['weight']);
        });

        it('validates note length', function () {
            $data = [
                'date' => '2024-03-25',
                'weight' => 75.0,
                'note' => str_repeat('a', 1001),
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/metrics', $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['note']);
        });

        it('rejects future dates', function () {
            $data = [
                'date' => '2030-01-01',
                'weight' => 75.0,
            ];

            $response = $this->actingAs($this->user)
                ->postJson('/api/metrics', $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['date']);
        });
    });

    describe('GET /api/metrics/{id}', function () {
        it('returns specific metric', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/metrics/{$this->metric->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'date',
                        'weight',
                        'note',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message',
                ]);

            expect($response->json('data.id'))->toBe($this->metric->id);
        });

        it('returns 404 for non-existent metric', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/metrics/999');

            $response->assertStatus(404);
        });

        it('returns 404 for metric belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMetric = Metric::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/metrics/{$otherMetric->id}");

            $response->assertStatus(404);
        });
    });

    describe('PUT /api/metrics/{id}', function () {
        it('updates metric', function () {
            $data = [
                'date' => '2024-03-16',
                'weight' => 76.0,
                'note' => 'Updated metric',
            ];

            $response = $this->actingAs($this->user)
                ->putJson("/api/metrics/{$this->metric->id}", $data);

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'date',
                        'weight',
                        'note',
                        'user' => [
                            'id',
                            'name',
                        ],
                        'created_at',
                        'updated_at',
                    ],
                    'message',
                ]);

            expect($response->json('data.weight'))->toBe('76.00');
            expect($response->json('data.note'))->toBe('Updated metric');

            $this->assertDatabaseHas('metrics', [
                'id' => $this->metric->id,
                'weight' => '76.00',
                'note' => 'Updated metric',
            ]);
        });

        it('validates data when updating', function () {
            $data = [
                'date' => 'invalid-date',
                'weight' => -10,
            ];

            $response = $this->actingAs($this->user)
                ->putJson("/api/metrics/{$this->metric->id}", $data);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['date', 'weight']);
        });

        it('returns 404 for metric belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMetric = Metric::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            $data = [
                'date' => '2024-03-16',
                'weight' => 76.0,
            ];

            $response = $this->actingAs($this->user)
                ->putJson("/api/metrics/{$otherMetric->id}", $data);

            $response->assertStatus(404);
        });
    });

    describe('DELETE /api/metrics/{id}', function () {
        it('deletes metric', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/metrics/{$this->metric->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data',
                    'message',
                ]);

            $this->assertDatabaseMissing('metrics', [
                'id' => $this->metric->id,
            ]);
        });

        it('returns 404 for non-existent metric', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson('/api/metrics/999');

            $response->assertStatus(404);
        });

        it('returns 404 for metric belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMetric = Metric::factory()->create([
                'user_id' => $otherUser->id,
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/metrics/{$otherMetric->id}");

            $response->assertStatus(404);
        });
    });

    describe('Authentication', function () {
        test('protected endpoints require authentication', function (string $method, string $url, array $data = []) {
            $response = $this->json($method, $url, $data);

            $response->assertStatus(401);
        })->with('protected_endpoints');
    });
});
