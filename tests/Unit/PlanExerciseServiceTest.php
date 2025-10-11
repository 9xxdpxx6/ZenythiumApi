<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;
use App\Services\PlanExerciseService;

describe('PlanExerciseService', function () {
    beforeEach(function () {
        $this->service = new PlanExerciseService();
        $this->user = User::factory()->create();
        $this->muscleGroup = MuscleGroup::factory()->create(['user_id' => $this->user->id]);
        $this->exercise = Exercise::factory()->create(['user_id' => $this->user->id, 'muscle_group_id' => $this->muscleGroup->id]);
        $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
        $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    });

    describe('getByPlanId', function () {
        it('returns plan exercises for valid plan', function () {
            $planExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 1
            ]);

            $result = $this->service->getByPlanId($this->plan->id, $this->user->id);

            expect($result)->not->toBeNull();
            expect($result)->toHaveCount(1);
            expect($result->first()->id)->toBe($planExercise->id);
        });

        it('returns empty collection for plan with no exercises', function () {
            $result = $this->service->getByPlanId($this->plan->id, $this->user->id);

            expect($result)->not->toBeNull();
            expect($result)->toHaveCount(0);
        });

        it('returns null for non-existent plan', function () {
            $result = $this->service->getByPlanId(999, $this->user->id);

            expect($result)->toBeNull();
        });

        it('returns null for plan belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $result = $this->service->getByPlanId($otherPlan->id, $this->user->id);

            expect($result)->toBeNull();
        });

        it('returns exercises without user check when userId is null', function () {
            $planExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id
            ]);

            $result = $this->service->getByPlanId($this->plan->id, null);

            expect($result)->not->toBeNull();
            expect($result)->toHaveCount(1);
        });
    });

    describe('create', function () {
        it('creates plan exercise successfully', function () {
            $data = [
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 1
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->not->toBeNull();
            expect($result->plan_id)->toBe($this->plan->id);
            expect($result->exercise_id)->toBe($this->exercise->id);
            expect($result->order)->toBe(1);

            $this->assertDatabaseHas('plan_exercises', [
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 1
            ]);
        });

        it('auto-assigns order when not provided', function () {
            $data = [
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->not->toBeNull();
            expect($result->order)->toBe(1);
        });

        it('assigns next order when exercises exist', function () {
            PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 3
            ]);

            $otherExercise = Exercise::factory()->create(['user_id' => $this->user->id, 'muscle_group_id' => $this->muscleGroup->id]);
            $data = [
                'plan_id' => $this->plan->id,
                'exercise_id' => $otherExercise->id
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->not->toBeNull();
            expect($result->order)->toBe(4);
        });

        it('returns null for non-existent plan', function () {
            $data = [
                'plan_id' => 999,
                'exercise_id' => $this->exercise->id
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->toBeNull();
        });

        it('returns null for non-existent exercise', function () {
            $data = [
                'plan_id' => $this->plan->id,
                'exercise_id' => 999
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->toBeNull();
        });

        it('returns null for plan belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);

            $data = [
                'plan_id' => $otherPlan->id,
                'exercise_id' => $this->exercise->id
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->toBeNull();
        });

        it('returns null for exercise belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherMuscleGroup = MuscleGroup::factory()->create(['user_id' => $otherUser->id]);
            $otherExercise = Exercise::factory()->create(['user_id' => $otherUser->id, 'muscle_group_id' => $otherMuscleGroup->id]);

            $data = [
                'plan_id' => $this->plan->id,
                'exercise_id' => $otherExercise->id
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->toBeNull();
        });

        it('returns null for duplicate exercise in plan', function () {
            PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id
            ]);

            $data = [
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id
            ];

            $result = $this->service->create($data, $this->user->id);

            expect($result)->toBeNull();
        });
    });

    describe('update', function () {
        beforeEach(function () {
            $this->planExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
                'order' => 1
            ]);
        });

        it('updates plan exercise successfully', function () {
            $data = ['order' => 2];

            $result = $this->service->update($this->planExercise->id, $data, $this->user->id);

            expect($result)->not->toBeNull();
            expect($result->order)->toBe(2);

            $this->assertDatabaseHas('plan_exercises', [
                'id' => $this->planExercise->id,
                'order' => 2
            ]);
        });

        it('returns null for non-existent plan exercise', function () {
            $data = ['order' => 2];

            $result = $this->service->update(999, $data, $this->user->id);

            expect($result)->toBeNull();
        });

        it('returns null for plan exercise belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherMuscleGroup = MuscleGroup::factory()->create(['user_id' => $otherUser->id]);
            $otherExercise = Exercise::factory()->create(['user_id' => $otherUser->id, 'muscle_group_id' => $otherMuscleGroup->id]);
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $otherPlan->id,
                'exercise_id' => $otherExercise->id
            ]);

            $data = ['order' => 2];

            $result = $this->service->update($otherPlanExercise->id, $data, $this->user->id);

            expect($result)->toBeNull();
        });

        it('validates plan id when provided', function () {
            $otherPlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $data = ['order' => 2];

            $result = $this->service->update($this->planExercise->id, $data, $this->user->id, $otherPlan->id);

            expect($result)->toBeNull();
        });
    });

    describe('delete', function () {
        beforeEach(function () {
            $this->planExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id
            ]);
        });

        it('deletes plan exercise successfully', function () {
            $result = $this->service->delete($this->planExercise->id, $this->user->id);

            expect($result)->toBeTrue();

            $this->assertDatabaseMissing('plan_exercises', [
                'id' => $this->planExercise->id
            ]);
        });

        it('returns false for non-existent plan exercise', function () {
            $result = $this->service->delete(999, $this->user->id);

            expect($result)->toBeFalse();
        });

        it('returns false for plan exercise belonging to another user', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherMuscleGroup = MuscleGroup::factory()->create(['user_id' => $otherUser->id]);
            $otherExercise = Exercise::factory()->create(['user_id' => $otherUser->id, 'muscle_group_id' => $otherMuscleGroup->id]);
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $otherPlan->id,
                'exercise_id' => $otherExercise->id
            ]);

            $result = $this->service->delete($otherPlanExercise->id, $this->user->id);

            expect($result)->toBeFalse();
        });

        it('validates plan id when provided', function () {
            $otherPlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);

            $result = $this->service->delete($this->planExercise->id, $this->user->id, $otherPlan->id);

            expect($result)->toBeFalse();
        });
    });
});
