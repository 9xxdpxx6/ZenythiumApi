<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;

describe('PlanExerciseController', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->muscleGroup = MuscleGroup::factory()->create();
        $this->exercise = Exercise::factory()->create(['user_id' => $this->user->id, 'muscle_group_id' => $this->muscleGroup->id]);
        $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
        $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    });

    describe('GET /api/v1/plans/{plan}/exercises', function () {
        it('returns exercises for a plan', function () {
            $planExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 1
            ]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/plans/{$this->plan->id}/exercises");

            $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'order',
                            'exercise' => [
                                'id',
                                'name',
                                'description',
                                'muscle_group' => [
                                    'id',
                                    'name'
                                ]
                            ],
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'message'
                ]);

            expect($response->json('data.0.id'))->toBe($planExercise->id);
            expect($response->json('data.0.exercise.id'))->toBe($this->exercise->id);
        });

        it('returns empty array for plan with no exercises', function () {
            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/plans/{$this->plan->id}/exercises");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [],
                    'message' => 'Упражнения плана успешно получены'
                ]);
        });

        it('returns 404 for non-existent plan', function () {
            $response = $this->actingAs($this->user)
                ->getJson('/api/v1/plans/999/exercises');

            $response->assertStatus(404)
                ->assertJson([
                    'message' => 'План не найден'
                ]);
        });

        it('returns 404 for plan belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $response = $this->actingAs($this->user)
                ->getJson("/api/v1/plans/{$otherPlan->id}/exercises");

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->getJson("/api/v1/plans/{$this->plan->id}/exercises");

            $response->assertStatus(401);
        });
    });

    describe('POST /api/v1/plans/{plan}/exercises', function () {
        it('adds exercise to plan', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->plan->id}/exercises", [
                    'exercise_id' => $this->exercise->id,
                    'order' => 1
                ]);

            $response->assertStatus(201)
                ->assertJsonStructure([
                    'data' => [
                        'id',
                        'order',
                        'exercise' => [
                            'id',
                            'name',
                            'description',
                            'muscle_group' => [
                                'id',
                                'name'
                            ]
                        ],
                        'created_at',
                        'updated_at'
                    ],
                    'message'
                ]);

            expect($response->json('data.exercise.id'))->toBe($this->exercise->id);
            expect($response->json('data.order'))->toBe(1);

            $this->assertDatabaseHas('plan_exercises', [
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 1
            ]);
        });

        it('auto-assigns order if not provided', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->plan->id}/exercises", [
                    'exercise_id' => $this->exercise->id
                ]);

            $response->assertStatus(201);
            expect($response->json('data.order'))->toBe(1);
        });

        it('validates exercise_id is required', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->plan->id}/exercises", []);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['exercise_id']);
        });

        it('validates exercise exists', function () {
            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->plan->id}/exercises", [
                    'exercise_id' => 999
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['exercise_id']);
        });

        it('prevents duplicate exercises in same plan', function () {
            PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id
            ]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->plan->id}/exercises", [
                    'exercise_id' => $this->exercise->id
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['exercise_id']);
        });

        it('returns 404 for non-existent plan', function () {
            $response = $this->actingAs($this->user)
                ->postJson('/api/v1/plans/999/exercises', [
                    'exercise_id' => $this->exercise->id
                ]);

            $response->assertStatus(404);
        });

        it('returns 404 for exercise belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMuscleGroup = MuscleGroup::factory()->create();
            $otherExercise = Exercise::factory()->create(['user_id' => $otherUser->id, 'muscle_group_id' => $otherMuscleGroup->id]);

            $response = $this->actingAs($this->user)
                ->postJson("/api/v1/plans/{$this->plan->id}/exercises", [
                    'exercise_id' => $otherExercise->id
                ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->postJson("/api/v1/plans/{$this->plan->id}/exercises", [
                'exercise_id' => $this->exercise->id
            ]);

            $response->assertStatus(401);
        });
    });

    describe('PUT /api/v1/plans/{plan}/exercises/{planExercise}', function () {
        beforeEach(function () {
            $this->planExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 1
            ]);
        });

        it('updates plan exercise order', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$this->plan->id}/exercises/{$this->planExercise->id}", [
                    'order' => 2
                ]);

            $response->assertStatus(200)
                ->assertJson([
                    'data' => [
                        'id' => $this->planExercise->id,
                        'order' => 2
                    ],
                    'message' => 'Упражнение в плане успешно обновлено'
                ]);

            $this->assertDatabaseHas('plan_exercises', [
                'id' => $this->planExercise->id,
                'order' => 2
            ]);
        });

        it('validates order is integer', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$this->plan->id}/exercises/{$this->planExercise->id}", [
                    'order' => 'invalid'
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['order']);
        });

        it('validates order is positive', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$this->plan->id}/exercises/{$this->planExercise->id}", [
                    'order' => 0
                ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['order']);
        });

        it('returns 404 for non-existent plan exercise', function () {
            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$this->plan->id}/exercises/999", [
                    'order' => 2
                ]);

            $response->assertStatus(404);
        });

        it('returns 404 for plan exercise belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherMuscleGroup = MuscleGroup::factory()->create();
            $otherExercise = Exercise::factory()->create(['user_id' => $otherUser->id, 'muscle_group_id' => $otherMuscleGroup->id]);
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $otherPlan->id,
                'exercise_id' => $otherExercise->id
            ]);

            $response = $this->actingAs($this->user)
                ->putJson("/api/v1/plans/{$otherPlan->id}/exercises/{$otherPlanExercise->id}", [
                    'order' => 2
                ]);

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->putJson("/api/v1/plans/{$this->plan->id}/exercises/{$this->planExercise->id}", [
                'order' => 2
            ]);

            $response->assertStatus(401);
        });
    });

    describe('DELETE /api/v1/plans/{plan}/exercises/{planExercise}', function () {
        beforeEach(function () {
            $this->planExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id
            ]);
        });

        it('deletes plan exercise', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/plans/{$this->plan->id}/exercises/{$this->planExercise->id}");

            $response->assertStatus(200)
                ->assertJson([
                    'data' => null,
                    'message' => 'Упражнение успешно удалено из плана'
                ]);

            $this->assertDatabaseMissing('plan_exercises', [
                'id' => $this->planExercise->id
            ]);
        });

        it('returns 404 for non-existent plan exercise', function () {
            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/plans/{$this->plan->id}/exercises/999");

            $response->assertStatus(404);
        });

        it('returns 404 for plan exercise belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherMuscleGroup = MuscleGroup::factory()->create();
            $otherExercise = Exercise::factory()->create(['user_id' => $otherUser->id, 'muscle_group_id' => $otherMuscleGroup->id]);
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $otherPlan->id,
                'exercise_id' => $otherExercise->id
            ]);

            $response = $this->actingAs($this->user)
                ->deleteJson("/api/v1/plans/{$otherPlan->id}/exercises/{$otherPlanExercise->id}");

            $response->assertStatus(404);
        });

        it('requires authentication', function () {
            $response = $this->deleteJson("/api/v1/plans/{$this->plan->id}/exercises/{$this->planExercise->id}");

            $response->assertStatus(401);
        });
    });
});
