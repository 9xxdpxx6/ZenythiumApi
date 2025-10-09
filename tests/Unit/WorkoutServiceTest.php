<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use App\Services\WorkoutService;

dataset('exception_scenarios', [
    'non_existent' => [999999, 'non-existent workout'],
    'other_user' => [null, 'workout from other user'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $this->workout = Workout::factory()->completed()->create([
        'plan_id' => $this->plan->id,
        'user_id' => $this->user->id,
    ]);
    $this->workoutService = new WorkoutService();
});

describe('WorkoutService', function () {
    describe('getAll', function () {
        it('returns paginated workouts with user filter', function () {
            Workout::factory()->count(5)->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $filters = ['user_id' => $this->user->id];
            $result = $this->workoutService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(6); // 5 new + 1 existing
            expect($result->items()[0])->toBeInstanceOf(Workout::class);
        });

        it('returns empty paginator when user_id is null', function () {
            $filters = ['user_id' => null];
            $result = $this->workoutService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
            expect($result->total())->toBe(0);
        });

        it('returns empty paginator when user_id is not provided', function () {
            $result = $this->workoutService->getAll([]);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
            expect($result->total())->toBe(0);
        });

        it('applies filters correctly', function () {
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

            $filters = [
                'user_id' => $this->user->id,
                'search' => 'Test',
            ];
            
            $result = $this->workoutService->getAll($filters);
            
            expect($result->count())->toBe(1);
            expect($result->items()[0]->plan->name)->toBe('Test Plan');
        });

        it('applies plan filter correctly', function () {
            $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            
            Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);

            $filters = [
                'user_id' => $this->user->id,
                'plan_id' => $plan1->id,
            ];
            
            $result = $this->workoutService->getAll($filters);
            
            expect($result->count())->toBe(1);
            expect($result->items()[0]->plan_id)->toBe($plan1->id);
        });

        it('applies completion filter correctly', function () {
            Workout::factory()->completed()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            Workout::factory()->inProgress()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);

            $filters = [
                'user_id' => $this->user->id,
                'completed' => 'true',
            ];
            
            $result = $this->workoutService->getAll($filters);
            
            expect($result->count())->toBe(2); // completed + existing completed
        });
    });

    describe('getById', function () {
        it('returns workout by id', function () {
            $workout = $this->workoutService->getById($this->workout->id, $this->user->id);
            
            expect($workout)->toBeInstanceOf(Workout::class);
            expect($workout->id)->toBe($this->workout->id);
        });

        it('returns workout without user filter', function () {
            $workout = $this->workoutService->getById($this->workout->id);
            
            expect($workout)->toBeInstanceOf(Workout::class);
            expect($workout->id)->toBe($this->workout->id);
        });

        it('throws exception for invalid workout access', function ($workoutId, $scenario) {
            if ($scenario === 'workout from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
                $otherWorkout = Workout::factory()->create([
                    'plan_id' => $otherPlan->id,
                    'user_id' => $otherUser->id,
                ]);
                $workoutId = $otherWorkout->id;
            }
            
            expect(fn() => $this->workoutService->getById($workoutId, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });

    describe('create', function () {
        it('creates a new workout', function () {
            $data = [
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 11:30:00',
            ];
            
            $workout = $this->workoutService->create($data);
            
            expect($workout)->toBeInstanceOf(Workout::class);
            expect($workout->plan_id)->toBe($this->plan->id);
            expect($workout->user_id)->toBe($this->user->id);
            expect($workout->started_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 10:00:00');
            expect($workout->finished_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 11:30:00');
            
            $this->assertDatabaseHas('workouts', [
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-15 10:00:00',
                'finished_at' => '2024-03-15 11:30:00',
            ]);
        });

        it('creates workout with minimal required data', function () {
            $data = [
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
                'started_at' => '2024-03-15 10:00:00',
            ];
            
            $workout = $this->workoutService->create($data);
            
            expect($workout)->toBeInstanceOf(Workout::class);
            expect($workout->plan_id)->toBe($this->plan->id);
            expect($workout->user_id)->toBe($this->user->id);
            expect($workout->finished_at)->toBeNull();
        });
    });

    describe('update', function () {
        it('updates a workout', function () {
            $data = [
                'started_at' => '2024-03-15 09:00:00',
                'finished_at' => '2024-03-15 10:30:00',
            ];
            
            $workout = $this->workoutService->update($this->workout->id, $data, $this->user->id);
            
            expect($workout)->toBeInstanceOf(Workout::class);
            expect($workout->id)->toBe($this->workout->id);
            expect($workout->started_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 09:00:00');
            expect($workout->finished_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 10:30:00');
            
            $this->assertDatabaseHas('workouts', [
                'id' => $this->workout->id,
                'started_at' => '2024-03-15 09:00:00',
                'finished_at' => '2024-03-15 10:30:00',
            ]);
        });

        it('updates workout without user filter', function () {
            $data = ['started_at' => '2024-03-15 09:00:00'];
            
            $workout = $this->workoutService->update($this->workout->id, $data);
            
            expect($workout)->toBeInstanceOf(Workout::class);
            expect($workout->started_at->format('Y-m-d H:i:s'))->toBe('2024-03-15 09:00:00');
        });

        it('throws exception for invalid workout access', function ($workoutId, $scenario) {
            $data = ['started_at' => '2024-03-15 09:00:00'];
            
            if ($scenario === 'workout from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
                $otherWorkout = Workout::factory()->create([
                    'plan_id' => $otherPlan->id,
                    'user_id' => $otherUser->id,
                ]);
                $workoutId = $otherWorkout->id;
            }
            
            expect(fn() => $this->workoutService->update($workoutId, $data, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });

    describe('delete', function () {
        it('deletes a workout', function () {
            $result = $this->workoutService->delete($this->workout->id, $this->user->id);
            
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('workouts', [
                'id' => $this->workout->id,
            ]);
        });

        it('deletes workout without user filter', function () {
            $newWorkout = Workout::factory()->create([
                'plan_id' => $this->plan->id,
                'user_id' => $this->user->id,
            ]);
            
            $result = $this->workoutService->delete($newWorkout->id);
            
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('workouts', [
                'id' => $newWorkout->id,
            ]);
        });

        it('throws exception for invalid workout access', function ($workoutId, $scenario) {
            if ($scenario === 'workout from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
                $otherWorkout = Workout::factory()->create([
                    'plan_id' => $otherPlan->id,
                    'user_id' => $otherUser->id,
                ]);
                $workoutId = $otherWorkout->id;
            }
            
            expect(fn() => $this->workoutService->delete($workoutId, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });
});
