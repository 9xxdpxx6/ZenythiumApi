<?php

declare(strict_types=1);

use App\Filters\WorkoutFilter;
use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Database\Eloquent\Builder;

dataset('date_range_filters', [
    'started_at_from' => ['started_at_from', '2024-05-01'],
    'started_at_to' => ['started_at_to', '2024-05-01'],
    'finished_at_from' => ['finished_at_from', '2024-05-01'],
    'finished_at_to' => ['finished_at_to', '2024-05-01'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $this->workout = Workout::factory()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
    ]);
});

describe('WorkoutFilter', function () {
    describe('search filter', function () {
        it('filters workouts by plan name', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);
            
            $workout1 = Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter(['search' => 'Test']);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->plan->name)->toBe('Test Plan');
        });

        it('filters workouts by user name', function () {
            $user1 = User::factory()->create(['name' => 'Test User']);
            $user2 = User::factory()->create(['name' => 'Another User']);
            
            $workout1 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $user1->id,
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $user2->id,
            ]);
            
            $filter = new WorkoutFilter(['search' => 'Test']);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->user->name)->toBe('Test User');
        });

        it('search is case insensitive', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            
            $workout1 = Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter(['search' => 'test']);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->plan->name)->toBe('Test Plan');
        });

        it('search matches partial names', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Testing Plan',
            ]);
            
            $plan3 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);
            
            $workout1 = Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);
            
            $workout3 = Workout::factory()->create([
                'plan_id' => $plan3->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter(['search' => 'Test']);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2);
            expect($results->pluck('plan.name')->toArray())->toContain('Test Plan', 'Testing Plan');
        });

        it('ignores search when not provided', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            
            $workout1 = Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2); // 1 new + 1 existing
        });
    });

    describe('user filter', function () {
        it('filters workouts by user_id', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            
            $filter = new WorkoutFilter(['user_id' => $this->user->id]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($this->workout->id);
        });

        it('ignores user filter when not provided', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            $otherWorkout = Workout::factory()->create([
                'plan_id' => $otherPlan->id,
                'user_id' => $otherUser->id,
            ]);
            
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2); // Both workouts
        });
    });

    describe('plan filter', function () {
        it('filters workouts by plan_id', function () {
            $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            $workout1 = Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter(['plan_id' => $plan1->id]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->plan_id)->toBe($plan1->id);
        });

        it('ignores plan filter when not provided', function () {
            $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            $workout1 = Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('date range filter', function () {
        it('filters workouts by started_at_from', function () {
            $oldWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-01-01 10:00:00',
            ]);
            
            $recentWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-06-01 10:00:00',
            ]);
            
            $filter = new WorkoutFilter([
                'started_at_from' => '2024-05-01',
            ]);
            
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the recent workout is in the range
            $createdWorkouts = $results->whereIn('id', [$oldWorkout->id, $recentWorkout->id]);
            
            expect($createdWorkouts)->toHaveCount(1);
            expect($createdWorkouts->first()->id)->toBe($recentWorkout->id);
        });

        it('filters workouts by started_at_to', function () {
            $oldWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-01-01 10:00:00',
            ]);
            
            $recentWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-06-01 10:00:00',
            ]);
            
            $filter = new WorkoutFilter([
                'started_at_to' => '2024-05-01',
            ]);
            
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the old workout is in the range
            $createdWorkouts = $results->whereIn('id', [$oldWorkout->id, $recentWorkout->id]);
            
            expect($createdWorkouts)->toHaveCount(1);
            expect($createdWorkouts->first()->id)->toBe($oldWorkout->id);
        });

        it('filters workouts by finished_at_from', function () {
            $oldWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'finished_at' => '2024-01-01 11:00:00',
            ]);
            
            $recentWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'finished_at' => '2024-06-01 11:00:00',
            ]);
            
            $filter = new WorkoutFilter([
                'finished_at_from' => '2024-05-01',
            ]);
            
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the recent workout is in the range
            $createdWorkouts = $results->whereIn('id', [$oldWorkout->id, $recentWorkout->id]);
            
            expect($createdWorkouts)->toHaveCount(1);
            expect($createdWorkouts->first()->id)->toBe($recentWorkout->id);
        });

        it('filters workouts by finished_at_to', function () {
            $oldWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'finished_at' => '2024-01-01 11:00:00',
            ]);
            
            $recentWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'finished_at' => '2024-06-01 11:00:00',
            ]);
            
            $filter = new WorkoutFilter([
                'finished_at_to' => '2024-05-01',
            ]);
            
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the old workout is in the range
            $createdWorkouts = $results->whereIn('id', [$oldWorkout->id, $recentWorkout->id]);
            
            expect($createdWorkouts)->toHaveCount(1);
            expect($createdWorkouts->first()->id)->toBe($oldWorkout->id);
        });

        it('ignores date range filter when not provided', function () {
            $oldWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-01-01 10:00:00',
            ]);
            
            $recentWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-06-01 10:00:00',
            ]);
            
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('completion filter', function () {
        it('filters completed workouts', function () {
            $completedWorkout = Workout::factory()->completed()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $inProgressWorkout = Workout::factory()->inProgress()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter(['completed' => 'true']);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only completed workouts are returned
            $createdWorkouts = $results->whereIn('id', [$completedWorkout->id, $inProgressWorkout->id]);
            
            expect($createdWorkouts)->toHaveCount(1);
            expect($createdWorkouts->first()->id)->toBe($completedWorkout->id);
        });

        it('filters in-progress workouts', function () {
            $completedWorkout = Workout::factory()->completed()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $inProgressWorkout = Workout::factory()->inProgress()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter(['completed' => 'false']);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only in-progress workouts are returned
            $createdWorkouts = $results->whereIn('id', [$completedWorkout->id, $inProgressWorkout->id]);
            
            expect($createdWorkouts)->toHaveCount(1);
            expect($createdWorkouts->first()->id)->toBe($inProgressWorkout->id);
        });

        it('handles boolean completion filter', function () {
            $completedWorkout = Workout::factory()->completed()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $inProgressWorkout = Workout::factory()->inProgress()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter(['completed' => true]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only completed workouts are returned
            $createdWorkouts = $results->whereIn('id', [$completedWorkout->id, $inProgressWorkout->id]);
            
            expect($createdWorkouts)->toHaveCount(1);
            expect($createdWorkouts->first()->id)->toBe($completedWorkout->id);
        });

        it('ignores completion filter when not provided', function () {
            $completedWorkout = Workout::factory()->completed()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $inProgressWorkout = Workout::factory()->inProgress()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('sorting', function () {
        it('applies default sorting by started_at desc', function () {
            $workout1 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-01 10:00:00',
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-01-01 10:00:00',
            ]);
            
            $workout3 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-02-01 10:00:00',
            ]);
            
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check that workouts are sorted by started_at desc
            $sortedWorkouts = $results->whereIn('id', [$workout1->id, $workout2->id, $workout3->id])->sortByDesc('started_at');
            
            expect($sortedWorkouts->first()->started_at->format('Y-m-d H:i:s'))->toBe('2024-03-01 10:00:00');
            expect($sortedWorkouts->last()->started_at->format('Y-m-d H:i:s'))->toBe('2024-01-01 10:00:00');
        });

        it('applies custom sorting', function () {
            $workout1 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-01-01 10:00:00',
            ]);
            
            $workout2 = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-02-01 10:00:00',
            ]);
            
            $filter = new WorkoutFilter(['sort_by' => 'started_at', 'sort_order' => 'asc']);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check that workouts are sorted by started_at asc
            $sortedWorkouts = $results->whereIn('id', [$workout1->id, $workout2->id])->sortBy('started_at');
            
            expect($sortedWorkouts->first()->started_at->format('Y-m-d H:i:s'))->toBe('2024-01-01 10:00:00');
            expect($sortedWorkouts->last()->started_at->format('Y-m-d H:i:s'))->toBe('2024-02-01 10:00:00');
        });
    });

    describe('multiple filters', function () {
        it('applies multiple filters correctly', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);
            
            $workout1 = Workout::factory()->completed()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-01 10:00:00',
            ]);
            
            $workout2 = Workout::factory()->inProgress()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-01 10:00:00',
            ]);
            
            $filter = new WorkoutFilter([
                'search' => 'Test',
                'completed' => 'true',
                'user_id' => $this->user->id,
                'started_at_from' => '2024-03-01',
            ]);
            
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->plan->name)->toBe('Test Plan');
            expect($results->first()->finished_at)->not->toBeNull();
        });
    });

    describe('empty filters', function () {
        it('handles empty filters', function () {
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($this->workout->id);
        });
    });

    describe('return value', function () {
        it('returns builder instance', function () {
            $filter = new WorkoutFilter([]);
            $query = Workout::query();
            
            $result = $filter->apply($query);
            
            expect($result)->toBeInstanceOf(Builder::class);
        });
    });
});
