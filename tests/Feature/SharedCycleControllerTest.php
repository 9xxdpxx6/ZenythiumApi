<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\SharedCycle;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->viewer = User::factory()->create();
    
    $this->cycle = Cycle::factory()->create(['user_id' => $this->owner->id]);
    
    $muscleGroup = MuscleGroup::factory()->create();
    $plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $exercise = Exercise::factory()->create([
        'user_id' => $this->owner->id,
        'muscle_group_id' => $muscleGroup->id,
    ]);
    PlanExercise::factory()->create([
        'plan_id' => $plan->id,
        'exercise_id' => $exercise->id,
    ]);

    $this->sharedCycle = SharedCycle::factory()->create([
        'cycle_id' => $this->cycle->id,
        'is_active' => true,
    ]);
});

describe('SharedCycleController', function () {
    describe('GET /api/v1/shared-cycles/{shareId}', function () {
        it('returns shared cycle data for authenticated user', function () {
            $response = $this->actingAs($this->viewer)
                ->getJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'name',
                        'weeks',
                        'author',
                        'plans_count',
                        'exercises_count',
                        'view_count',
                        'import_count',
                        'structure',
                    ],
                    'message',
                ]);
        });

        it('returns 401 for unauthenticated user', function () {
            $response = $this->getJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}");

            $response->assertStatus(401);
        });

        it('returns 422 for invalid UUID', function () {
            $response = $this->actingAs($this->viewer)
                ->getJson('/api/v1/shared-cycles/invalid-uuid');

            $response->assertStatus(422)
                ->assertJsonStructure([
                    'message',
                    'errors',
                ]);
        });

        it('returns 404 for non-existent share_id', function () {
            $response = $this->actingAs($this->viewer)
                ->getJson('/api/v1/shared-cycles/00000000-0000-0000-0000-000000000000');

            $response->assertStatus(404);
        });

        it('returns 410 for expired shared cycle', function () {
            $expiredCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
                'expires_at' => now()->subDay(),
            ]);

            $response = $this->actingAs($this->viewer)
                ->getJson("/api/v1/shared-cycles/{$expiredCycle->share_id}");

            $response->assertStatus(410);
        });

        it('returns 410 for inactive shared cycle', function () {
            $inactiveCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => false,
            ]);

            $response = $this->actingAs($this->viewer)
                ->getJson("/api/v1/shared-cycles/{$inactiveCycle->share_id}");

            $response->assertStatus(410);
        });

        it('increments view_count on successful request', function () {
            $initialCount = $this->sharedCycle->view_count;

            $this->actingAs($this->viewer)
                ->getJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}");

            $this->sharedCycle->refresh();
            expect($this->sharedCycle->view_count)->toBe($initialCount + 1);
        });
    });

    describe('POST /api/v1/shared-cycles/{shareId}/import', function () {
        it('imports cycle successfully', function () {
            $response = $this->actingAs($this->viewer)
                ->postJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}/import");

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'message',
                    'data' => [
                        'cycle_id',
                        'plans_count',
                        'exercises_count',
                    ],
                ]);

            // Проверяем, что цикл создан
            $cycleId = $response->json('data.cycle_id');
            $importedCycle = Cycle::find($cycleId);
            expect($importedCycle)->not->toBeNull();
            expect($importedCycle->user_id)->toBe($this->viewer->id);
        });

        it('returns 401 for unauthenticated user', function () {
            $response = $this->postJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}/import");

            $response->assertStatus(401);
        });

        it('returns 400 when trying to import own cycle', function () {
            $response = $this->actingAs($this->owner)
                ->postJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}/import");

            $response->assertStatus(400)
                ->assertJson([
                    'message' => 'Нельзя импортировать свою собственную программу',
                ]);
        });

        it('returns 404 for non-existent share_id', function () {
            $response = $this->actingAs($this->viewer)
                ->postJson('/api/v1/shared-cycles/00000000-0000-0000-0000-000000000000/import');

            $response->assertStatus(404);
        });

        it('returns 410 for expired shared cycle', function () {
            $expiredCycle = SharedCycle::factory()->create([
                'cycle_id' => $this->cycle->id,
                'is_active' => true,
                'expires_at' => now()->subDay(),
            ]);

            $response = $this->actingAs($this->viewer)
                ->postJson("/api/v1/shared-cycles/{$expiredCycle->share_id}/import");

            $response->assertStatus(410);
        });

        it('increments import_count after successful import', function () {
            $initialCount = $this->sharedCycle->import_count;

            $this->actingAs($this->viewer)
                ->postJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}/import");

            $this->sharedCycle->refresh();
            expect($this->sharedCycle->import_count)->toBe($initialCount + 1);
        });

        it('uses existing exercises if they exist for importer', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $existingExercise = Exercise::factory()->create([
                'user_id' => $this->viewer->id,
                'name' => $this->cycle->plans->first()->planExercises->first()->exercise->name,
                'muscle_group_id' => $muscleGroup->id,
            ]);

            $response = $this->actingAs($this->viewer)
                ->postJson("/api/v1/shared-cycles/{$this->sharedCycle->share_id}/import");

            $response->assertStatus(201);
            
            // Проверяем, что упражнение не дублируется
            $exerciseCount = Exercise::where('user_id', $this->viewer->id)
                ->where('name', $existingExercise->name)
                ->count();
            
            expect($exerciseCount)->toBe(1);
        });
    });
});

