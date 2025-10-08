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

test('can get plans list', function () {
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
});

test('can create a plan', function () {
    $planData = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
        'order' => 1,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/plans', $planData);

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
        ])
        ->assertJson([
            'data' => [
                'name' => 'Test Plan',
                'order' => 1,
            ],
            'message' => 'План успешно создан'
        ]);

    $this->assertDatabaseHas('plans', [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
        'order' => 1,
    ]);
});

test('can show a plan', function () {
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
        ])
        ->assertJson([
            'data' => [
                'id' => $this->plan->id,
                'name' => $this->plan->name,
                'order' => $this->plan->order,
            ],
            'message' => 'План успешно получен'
        ]);
});

test('can update a plan', function () {
    $updateData = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Updated Plan',
        'order' => 2,
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/plans/{$this->plan->id}", $updateData);

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
        ])
        ->assertJson([
            'data' => [
                'id' => $this->plan->id,
                'name' => 'Updated Plan',
                'order' => 2,
            ],
            'message' => 'План успешно обновлен'
        ]);

    $this->assertDatabaseHas('plans', [
        'id' => $this->plan->id,
        'cycle_id' => $this->cycle->id,
        'name' => 'Updated Plan',
        'order' => 2,
    ]);
});

test('can delete a plan', function () {
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

test('cannot access plans from other users', function () {
    $otherUser = User::factory()->create();
    $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
    $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);

    $response = $this->actingAs($this->user)
        ->getJson("/api/plans/{$otherPlan->id}");

    $response->assertStatus(404);
});

test('plan validation requires cycle_id', function () {
    $planData = [
        'name' => 'Test Plan',
        'order' => 1,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/plans', $planData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cycle_id']);
});

test('plan validation requires name', function () {
    $planData = [
        'cycle_id' => $this->cycle->id,
        'order' => 1,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/plans', $planData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('plan validation requires unique name per cycle', function () {
    $planData = [
        'cycle_id' => $this->cycle->id,
        'name' => $this->plan->name,
        'order' => 1,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/plans', $planData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

test('plan validation requires valid cycle_id', function () {
    $planData = [
        'cycle_id' => 999999,
        'name' => 'Test Plan',
        'order' => 1,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/plans', $planData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['cycle_id']);
});

test('plan validation requires positive order', function () {
    $planData = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
        'order' => 0,
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/plans', $planData);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order']);
});

test('plan can be created without order', function () {
    $planData = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan Without Order',
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/plans', $planData);

    $response->assertStatus(201)
        ->assertJson([
            'data' => [
                'name' => 'Test Plan Without Order',
                'order' => null,
            ],
            'message' => 'План успешно создан'
        ]);
});
