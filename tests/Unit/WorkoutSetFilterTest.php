<?php

declare(strict_types=1);

use App\Filters\WorkoutSetFilter;
use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\PlanExercise;
use App\Models\User;
use App\Models\Workout;
use App\Models\WorkoutSet;

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
    $this->workout = Workout::factory()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
    ]);
});

describe('WorkoutSetFilter', function () {
    describe('search filter', function () {
        it('filters by plan name', function () {
            $workoutSet = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $filter = new WorkoutSetFilter(['search' => $this->plan->name]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet->id);
        });

        it('filters by exercise name', function () {
            $workoutSet = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $filter = new WorkoutSetFilter(['search' => $this->exercise->name]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet->id);
        });

        it('filters by user name', function () {
            $workoutSet = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $filter = new WorkoutSetFilter(['search' => $this->user->name]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet->id);
        });

        it('returns empty when search term not found', function () {
            WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $filter = new WorkoutSetFilter(['search' => 'nonexistent']);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(0);
        });
    });

    describe('workout filter', function () {
        it('filters by workout_id', function () {
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $filter = new WorkoutSetFilter(['workout_id' => $this->workout->id]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet1->id);
        });
    });

    describe('plan exercise filter', function () {
        it('filters by plan_exercise_id', function () {
            $otherPlanExercise = PlanExercise::factory()->create([
                'plan_id' => $this->plan->id,
                'exercise_id' => $this->exercise->id,
            ]);
            
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $otherPlanExercise->id,
            ]);
            
            $filter = new WorkoutSetFilter(['plan_exercise_id' => $this->planExercise->id]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet1->id);
        });
    });

    describe('weight range filters', function () {
        it('filters by weight_from', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 30.0,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 50.0,
            ]);
            
            $filter = new WorkoutSetFilter(['weight_from' => 40.0]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet2->id);
        });

        it('filters by weight_to', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 30.0,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 50.0,
            ]);
            
            $filter = new WorkoutSetFilter(['weight_to' => 40.0]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet1->id);
        });

        it('filters by weight_min', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 30.0,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 50.0,
            ]);
            
            $filter = new WorkoutSetFilter(['weight_min' => 40.0]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet2->id);
        });

        it('filters by weight_max', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 30.0,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 50.0,
            ]);
            
            $filter = new WorkoutSetFilter(['weight_max' => 40.0]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet1->id);
        });
    });

    describe('reps range filters', function () {
        it('filters by reps_from', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 5,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 10,
            ]);
            
            $filter = new WorkoutSetFilter(['reps_from' => 8]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet2->id);
        });

        it('filters by reps_to', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 5,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 10,
            ]);
            
            $filter = new WorkoutSetFilter(['reps_to' => 8]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet1->id);
        });

        it('filters by reps_min', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 5,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 10,
            ]);
            
            $filter = new WorkoutSetFilter(['reps_min' => 8]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet2->id);
        });

        it('filters by reps_max', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 5,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'reps' => 10,
            ]);
            
            $filter = new WorkoutSetFilter(['reps_max' => 8]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet1->id);
        });
    });

    describe('user filter', function () {
        it('filters by user_id through workout relationship', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $otherWorkout->id,
                'plan_exercise_id' => $this->planExercise->id,
            ]);
            
            $filter = new WorkoutSetFilter(['user_id' => $this->user->id]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($workoutSet1->id);
        });
    });

    describe('sorting', function () {
        it('applies default sorting by created_at desc', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'created_at' => now()->subHour(),
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'created_at' => now(),
            ]);
            
            $filter = new WorkoutSetFilter([]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results->first()->id)->toBe($workoutSet2->id);
            expect($results->last()->id)->toBe($workoutSet1->id);
        });
    });

    describe('multiple filters', function () {
        it('applies multiple filters simultaneously', function () {
            $workoutSet1 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 30.0,
                'reps' => 5,
            ]);
            $workoutSet2 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 50.0,
                'reps' => 10,
            ]);
            $workoutSet3 = WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => $this->planExercise->id,
                'weight' => 70.0,
                'reps' => 15,
            ]);
            
            $filter = new WorkoutSetFilter([
                'weight_from' => 40.0,
                'reps_from' => 8,
            ]);
            $query = WorkoutSet::query();
            $filter->apply($query);
            
            $results = $query->get();
            expect($results)->toHaveCount(2);
            expect($results->pluck('id')->toArray())->toContain($workoutSet2->id);
            expect($results->pluck('id')->toArray())->toContain($workoutSet3->id);
            expect($results->pluck('id')->toArray())->not->toContain($workoutSet1->id);
        });
    });
});
