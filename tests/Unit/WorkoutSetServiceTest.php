<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;
use App\Services\WorkoutSetService;

dataset('exception_scenarios', [
    'non_existent' => [999999, 'non-existent workout set'],
    'other_user' => [null, 'workout set from other user'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $this->muscleGroup = MuscleGroup::factory()->create();
    $this->exercise = Exercise::factory()->create(['muscle_group_id' => $this->muscleGroup->id]);
    $this->planExercise = PlanExercise::factory()->create([
        'plan_id' => $this->plan->id,
        'exercise_id' => $this->exercise->id,
    ]);
    $this->workout = Workout::factory()->completed()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
    ]);
    $this->workoutSet = WorkoutSet::factory()->create([
        'workout_id' => $this->workout->id,
        'plan_exercise_id' => $this->planExercise->id,
        'weight' => 50.5,
        'reps' => 10,
    ]);
    $this->workoutSetService = new WorkoutSetService();
});

describe('WorkoutSetService', function () {
    describe('getAll', function () {
        it('returns paginated workout sets with workout filter', function () {
            WorkoutSet::factory()->count(5)->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $filters = ['workout_id' => $this->workout->id];
            $result = $this->workoutSetService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(6); // 5 new + 1 existing
            expect($result->items()[0])->toBeInstanceOf(WorkoutSet::class);
        });

        it('returns empty paginator when workout_id is null', function () {
            $filters = ['workout_id' => null];
            $result = $this->workoutSetService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
            expect($result->total())->toBe(0);
        });

        it('applies weight range filters correctly', function () {
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 30.0,
            ]);
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 80.0,
            ]);
            
            $filters = [
                'workout_id' => $this->workout->id,
                'weight_from' => 40.0,
                'weight_to' => 70.0,
            ];
            $result = $this->workoutSetService->getAll($filters);
            
            expect($result->count())->toBe(1);
            expect($result->items()[0]->weight)->toBe('50.50');
        });

        it('applies reps range filters correctly', function () {
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 5,
            ]);
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 15,
            ]);
            
            $filters = [
                'workout_id' => $this->workout->id,
                'reps_from' => 8,
                'reps_to' => 12,
            ];
            $result = $this->workoutSetService->getAll($filters);
            
            expect($result->count())->toBe(1);
            expect($result->items()[0]->reps)->toBe(10);
        });
    });

    describe('getById', function () {
        it('returns workout set by id', function () {
            $result = $this->workoutSetService->getById($this->workoutSet->id);
            
            expect($result)->toBeInstanceOf(WorkoutSet::class);
            expect($result->id)->toBe($this->workoutSet->id);
            expect($result->weight)->toBe('50.50');
            expect($result->reps)->toBe(10);
        });

        it('returns workout set with relationships loaded', function () {
            $result = $this->workoutSetService->getById($this->workoutSet->id);
            
            expect($result->relationLoaded('workout'))->toBeTrue();
            expect($result->relationLoaded('planExercise'))->toBeTrue();
            expect($result->workout->relationLoaded('plan'))->toBeTrue();
            expect($result->workout->plan->relationLoaded('cycle'))->toBeTrue();
        });

        it('filters by user when user_id provided', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            $otherWorkoutSet = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $result = $this->workoutSetService->getById($this->workoutSet->id, $this->user->id);
            expect($result->id)->toBe($this->workoutSet->id);
            
            expect(fn() => $this->workoutSetService->getById($otherWorkoutSet->id, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('throws exception for non-existent workout set', function () {
            expect(fn() => $this->workoutSetService->getById(999999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('create', function () {
        it('creates workout set successfully', function () {
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 75.0,
                'reps' => 12,
            ];
            
            $result = $this->workoutSetService->create($data);
            
            expect($result)->toBeInstanceOf(WorkoutSet::class);
            expect($result->workout_id)->toBe($this->workout->id);
            expect($result->plan_exercise_id)->toBe($this->planExercise->id);
            expect($result->weight)->toBe('75.00');
            expect($result->reps)->toBe(12);
            expect($result->exists)->toBeTrue();
        });

        it('creates workout set with nullable fields', function () {
            $data = [
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => null,
                'reps' => null,
            ];
            
            $result = $this->workoutSetService->create($data);
            
            expect($result)->toBeInstanceOf(WorkoutSet::class);
            expect($result->weight)->toBeNull();
            expect($result->reps)->toBeNull();
        });
    });

    describe('update', function () {
        it('updates workout set successfully', function () {
            $data = [
                'weight' => 60.0,
                'reps' => 15,
            ];
            
            $result = $this->workoutSetService->update($this->workoutSet->id, $data);
            
            expect($result)->toBeInstanceOf(WorkoutSet::class);
            expect($result->weight)->toBe('60.00');
            expect($result->reps)->toBe(15);
            expect($result->workout_id)->toBe($this->workout->id); // unchanged
        });

        it('filters by user when updating', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            $otherWorkoutSet = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $data = ['weight' => 100.0];
            
            expect(fn() => $this->workoutSetService->update($otherWorkoutSet->id, $data, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('throws exception for non-existent workout set', function () {
            expect(fn() => $this->workoutSetService->update(999999, ['weight' => 100.0]))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('delete', function () {
        it('deletes workout set successfully', function () {
            $result = $this->workoutSetService->delete($this->workoutSet->id);
            
            expect($result)->toBeTrue();
            expect(WorkoutSet::find($this->workoutSet->id))->toBeNull();
        });

        it('filters by user when deleting', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            $otherWorkoutSet = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            expect(fn() => $this->workoutSetService->delete($otherWorkoutSet->id, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });

        it('throws exception for non-existent workout set', function () {
            expect(fn() => $this->workoutSetService->delete(999999))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        });
    });

    describe('getByWorkoutId', function () {
        it('returns workout sets for specific workout', function () {
            WorkoutSet::factory()->count(3)->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $result = $this->workoutSetService->getByWorkoutId($this->workout->id);
            
            expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($result->count())->toBe(4); // 3 new + 1 existing
            expect($result->every(fn($item) => $item->workout_id === $this->workout->id))->toBeTrue();
        });

        it('filters by user when provided', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $result = $this->workoutSetService->getByWorkoutId($this->workout->id, $this->user->id);
            
            expect($result->count())->toBe(1);
            expect($result->first()->workout_id)->toBe($this->workout->id);
        });
    });

    describe('getByPlanExerciseId', function () {
        it('returns workout sets for specific plan exercise', function () {
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            WorkoutSet::factory()->count(2)->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $result = $this->workoutSetService->getByPlanExerciseId($this->planExercise->id);
            
            expect($result)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
            expect($result->count())->toBe(3); // 2 new + 1 existing
            expect($result->every(fn($item) => $item->plan_exercise_id === $this->planExercise->id))->toBeTrue();
        });

        it('filters by user when provided', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $result = $this->workoutSetService->getByPlanExerciseId($this->planExercise->id, $this->user->id);
            
            expect($result->count())->toBe(1);
            expect($result->first()->workout->user_id)->toBe($this->user->id);
        });
    });
});
