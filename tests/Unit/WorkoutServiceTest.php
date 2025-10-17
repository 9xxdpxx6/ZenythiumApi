<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workout;
use App\Services\WorkoutService;

dataset('exception_scenarios', [
    'non_existent' => [PHP_INT_MAX, 'non-existent workout'],
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
            
            $result = $this->workoutService->getById($workoutId, $this->user->id);
            expect($result)->toBeNull();
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
            
            $result = $this->workoutService->update($workoutId, $data, $this->user->id);
            expect($result)->toBeNull();
        })->with('exception_scenarios');
    });

    describe('delete', function () {
        it('deletes workout and related workout sets', function () {
            // Создаем WorkoutSet для этой тренировки
            $workoutSet = \App\Models\WorkoutSet::factory()->create([
                'workout_id' => $this->workout->id,
                'plan_exercise_id' => \App\Models\PlanExercise::factory()->create([
                    'plan_id' => $this->plan->id
                ])->id,
                'weight' => 80.5,
                'reps' => 10
            ]);

            $result = $this->workoutService->delete($this->workout->id, $this->user->id);
            
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('workouts', [
                'id' => $this->workout->id,
            ]);
            $this->assertDatabaseMissing('workout_sets', [
                'id' => $workoutSet->id,
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
            
            $result = $this->workoutService->delete($workoutId, $this->user->id);
            expect($result)->toBeFalse();
        })->with('exception_scenarios');
    });

    describe('determineNextPlan', function () {
        beforeEach(function () {
            // Для тестов determineNextPlan создаем только пользователя без циклов
            $this->user = User::factory()->create();
            $this->workoutService = new WorkoutService();
        });

        it('returns first plan when no workouts completed', function () {
            // Создаем новый цикл для этого теста
            $testCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            // Создаем цикл с несколькими планами
            $plan1 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'Plan 1'
            ]);
            $plan2 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 2,
                'is_active' => true,
                'name' => 'Plan 2'
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            expect($result)->toBe($plan1->id);
        });

        it('returns plan with least total workouts', function () {
            // Создаем новый цикл для этого теста
            $testCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            // Создаем цикл с несколькими планами
            $plan1 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'Plan 1'
            ]);
            $plan2 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 2,
                'is_active' => true,
                'name' => 'Plan 2'
            ]);

            // Создаем завершенные тренировки для plan1 (2 тренировки)
            Workout::factory()->completed()->count(2)->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);

            // Создаем завершенную тренировку для plan2 (1 тренировка)
            Workout::factory()->completed()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            expect($result)->toBe($plan2->id);
        });

        it('returns first plan by order when equal completed workouts', function () {
            // Создаем новый цикл для этого теста
            $testCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            // Создаем цикл с несколькими планами
            $plan1 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'Plan 1'
            ]);
            $plan2 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 2,
                'is_active' => true,
                'name' => 'Plan 2'
            ]);

            // Создаем одинаковое количество завершенных тренировок для обоих планов
            Workout::factory()->completed()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
            ]);
            Workout::factory()->completed()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            expect($result)->toBe($plan1->id);
        });

        it('ignores inactive plans', function () {
            // Создаем новый цикл для этого теста
            $testCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            // Создаем активный план
            $activePlan = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'Active Plan'
            ]);

            // Создаем неактивный план
            $inactivePlan = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 2,
                'is_active' => false,
                'name' => 'Inactive Plan'
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            expect($result)->toBe($activePlan->id);
        });

        it('returns null when no active cycle found', function () {
            // Создаем пользователя без циклов
            $userWithoutCycles = User::factory()->create();

            $result = $this->workoutService->determineNextPlan($userWithoutCycles->id);

            expect($result)->toBeNull();
        });

        it('returns null when cycle has no active plans', function () {
            // Создаем новый цикл для этого теста
            $testCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            // Создаем цикл только с неактивными планами
            $inactivePlan = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 1,
                'is_active' => false,
                'name' => 'Inactive Plan'
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            expect($result)->toBeNull();
        });

        it('returns -1 when all plans have active workouts', function () {
            // Создаем новый цикл для этого теста
            $testCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            // Создаем цикл с несколькими планами
            $plan1 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'Plan 1'
            ]);
            $plan2 = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 2,
                'is_active' => true,
                'name' => 'Plan 2'
            ]);

            // Создаем активные тренировки для обоих планов
            Workout::factory()->create([
                'plan_id' => $plan1->id,
                'user_id' => $this->user->id,
                'started_at' => now(),
                'finished_at' => null, // Активная тренировка
            ]);
            Workout::factory()->create([
                'plan_id' => $plan2->id,
                'user_id' => $this->user->id,
                'started_at' => now(),
                'finished_at' => null, // Активная тренировка
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            expect($result)->toBe(-1);
        });

        it('selects from most recent cycle when user has multiple cycles', function () {
            // Создаем старый цикл
            $oldCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'created_at' => now()->subDays(10)
            ]);
            $oldPlan = Plan::factory()->create([
                'cycle_id' => $oldCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'Old Plan'
            ]);

            // Создаем новый цикл
            $newCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'created_at' => now()
            ]);
            $newPlan = Plan::factory()->create([
                'cycle_id' => $newCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'New Plan'
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            expect($result)->toBe($newPlan->id);
        });

        it('ignores workouts from other users when counting completed workouts', function () {
            // Создаем новый цикл для этого теста
            $testCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            // Создаем план
            $plan = Plan::factory()->create([
                'cycle_id' => $testCycle->id,
                'order' => 1,
                'is_active' => true,
                'name' => 'Plan'
            ]);

            // Создаем завершенную тренировку для другого пользователя
            $otherUser = User::factory()->create();
            Workout::factory()->completed()->create([
                'plan_id' => $plan->id,
                'user_id' => $otherUser->id,
            ]);

            $result = $this->workoutService->determineNextPlan($this->user->id);

            // Должен вернуть план, так как у текущего пользователя нет завершенных тренировок
            expect($result)->toBe($plan->id);
        });
    });
});
