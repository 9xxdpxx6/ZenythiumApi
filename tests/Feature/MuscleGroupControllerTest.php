<?php

declare(strict_types=1);

use App\Models\MuscleGroup;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
});

describe('MuscleGroupController', function () {
    describe('GET /api/muscle-groups', function () {
        it('returns all muscle groups with exercises count for authenticated user', function () {
            // Create muscle groups
            $muscleGroup1 = MuscleGroup::factory()->create(['name' => 'Chest']);
            $muscleGroup2 = MuscleGroup::factory()->create(['name' => 'Back']);

            // Create exercises for the authenticated user
            $muscleGroup1->exercises()->create([
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'user_id' => $this->user->id,
            ]);

            $muscleGroup1->exercises()->create([
                'name' => 'Bench Press',
                'description' => 'Bench press exercise',
                'user_id' => $this->user->id,
            ]);

            // Create exercise for another user (should not be counted)
            $muscleGroup1->exercises()->create([
                'name' => 'Other User Exercise',
                'description' => 'Exercise for another user',
                'user_id' => User::factory()->create()->id,
            ]);

            $response = $this->getJson('/api/v1/muscle-groups');

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'created_at',
                            'updated_at',
                            'exercises_count',
                        ],
                    ],
                    'message',
                ]);

            // Check that exercises_count is correct for the authenticated user
            $chestGroup = collect($response->json('data'))->firstWhere('name', 'Chest');
            expect($chestGroup['exercises_count'])->toBe(2);
        });

        it('supports search filtering', function () {
            MuscleGroup::factory()->create(['name' => 'Chest']);
            MuscleGroup::factory()->create(['name' => 'Back']);
            MuscleGroup::factory()->create(['name' => 'Legs']);

            $response = $this->getJson('/api/v1/muscle-groups?search=chest');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(1);
            expect($response->json('data.0.name'))->toBe('Chest');
        });

        it('supports pagination', function () {
            MuscleGroup::factory()->count(25)->create();

            $response = $this->getJson('/api/v1/muscle-groups?per_page=10');

            $response->assertStatus(200);
            expect($response->json('data'))->toHaveCount(10);
        });
    });

    describe('mutation routes are not exposed to clients', function () {
        it('does not allow creating a muscle group', function () {
            $response = $this->postJson('/api/v1/muscle-groups', ['name' => 'Chest']);

            $response->assertStatus(405);
        });

        it('does not allow updating a muscle group', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);

            $response = $this->putJson("/api/v1/muscle-groups/{$muscleGroup->id}", ['name' => 'Updated']);

            $response->assertStatus(405);
            $this->assertDatabaseHas('muscle_groups', ['id' => $muscleGroup->id, 'name' => 'Chest']);
        });

        it('does not allow deleting a muscle group', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);

            $response = $this->deleteJson("/api/v1/muscle-groups/{$muscleGroup->id}");

            $response->assertStatus(405);
            $this->assertDatabaseHas('muscle_groups', ['id' => $muscleGroup->id]);
        });
    });

    describe('GET /api/muscle-groups/{id}', function () {
        it('returns a specific muscle group', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);

            // Add exercises for the authenticated user
            $muscleGroup->exercises()->create([
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'user_id' => $this->user->id,
            ]);

            $response = $this->getJson("/api/v1/muscle-groups/{$muscleGroup->id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'created_at',
                        'updated_at',
                        'exercises_count',
                    ],
                    'message',
                ]);

            expect($response->json('data.name'))->toBe('Chest');
            expect($response->json('data.exercises_count'))->toBe(1);
        });

        it('returns 404 for non-existent muscle group', function () {
            $response = $this->getJson('/api/muscle-groups/999');

            $response->assertStatus(404);
        });
    });

});
