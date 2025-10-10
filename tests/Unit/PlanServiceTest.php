<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;

dataset('exception_scenarios', [
    'non_existent' => [PHP_INT_MAX, 'non-existent plan'],
    'other_user' => [null, 'plan from other user'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $this->planService = new PlanService();
});

describe('PlanService', function () {
    describe('getAll', function () {
        it('returns paginated plans with user filter', function () {
            Plan::factory()->count(5)->create(['cycle_id' => $this->cycle->id]);
            
            $filters = ['user_id' => $this->user->id];
            $result = $this->planService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(6); // 5 new + 1 existing
            expect($result->items()[0])->toBeInstanceOf(Plan::class);
        });

        it('returns empty paginator when user_id is null', function () {
            $filters = ['user_id' => null];
            $result = $this->planService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
            expect($result->total())->toBe(0);
        });

        it('returns empty paginator when user_id is not provided', function () {
            $result = $this->planService->getAll([]);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
            expect($result->total())->toBe(0);
        });

        it('applies filters correctly', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
                'order' => 1,
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
                'order' => 2,
            ]);

            $filters = [
                'user_id' => $this->user->id,
                'search' => 'Test',
            ];
            
            $result = $this->planService->getAll($filters);
            
            expect($result->count())->toBe(1);
            expect($result->items()[0]->name)->toBe('Test Plan');
        });
    });

    describe('getById', function () {
        it('returns plan with cycle relationship', function () {
            $plan = $this->planService->getById($this->plan->id, $this->user->id);
            
            expect($plan)->toBeInstanceOf(Plan::class);
            expect($plan->id)->toBe($this->plan->id);
            expect($plan->cycle)->toBeInstanceOf(Cycle::class);
        });

        it('returns plan without user filter', function () {
            $plan = $this->planService->getById($this->plan->id);
            
            expect($plan)->toBeInstanceOf(Plan::class);
            expect($plan->id)->toBe($this->plan->id);
        });

        it('throws exception for invalid plan access', function ($planId, $scenario) {
            if ($scenario === 'plan from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
                $planId = $otherPlan->id;
            }
            
            expect(fn() => $this->planService->getById($planId, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });

    describe('create', function () {
        it('creates a new plan', function () {
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'New Plan',
                'order' => 1,
            ];
            
            $plan = $this->planService->create($data);
            
            expect($plan)->toBeInstanceOf(Plan::class);
            expect($plan->cycle_id)->toBe($this->cycle->id);
            expect($plan->name)->toBe('New Plan');
            expect($plan->order)->toBe(1);
            
            $this->assertDatabaseHas('plans', [
                'cycle_id' => $this->cycle->id,
                'name' => 'New Plan',
                'order' => 1,
            ]);
        });

        it('creates plan without order', function () {
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Plan Without Order',
            ];
            
            $plan = $this->planService->create($data);
            
            expect($plan)->toBeInstanceOf(Plan::class);
            expect($plan->name)->toBe('Plan Without Order');
            expect($plan->order)->toBeNull();
        });
    });

    describe('update', function () {
        it('updates a plan', function () {
            $data = [
                'name' => 'Updated Plan',
                'order' => 2,
            ];
            
            $plan = $this->planService->update($this->plan->id, $data, $this->user->id);
            
            expect($plan)->toBeInstanceOf(Plan::class);
            expect($plan->id)->toBe($this->plan->id);
            expect($plan->name)->toBe('Updated Plan');
            expect($plan->order)->toBe(2);
            expect($plan->cycle)->toBeInstanceOf(Cycle::class);
            
            $this->assertDatabaseHas('plans', [
                'id' => $this->plan->id,
                'name' => 'Updated Plan',
                'order' => 2,
            ]);
        });

        it('updates plan without user filter', function () {
            $data = ['name' => 'Updated Plan'];
            
            $plan = $this->planService->update($this->plan->id, $data);
            
            expect($plan)->toBeInstanceOf(Plan::class);
            expect($plan->name)->toBe('Updated Plan');
        });

        it('throws exception for invalid plan access', function ($planId, $scenario) {
            $data = ['name' => 'Updated Plan'];
            
            if ($scenario === 'plan from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
                $planId = $otherPlan->id;
            }
            
            expect(fn() => $this->planService->update($planId, $data, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });

    describe('delete', function () {
        it('deletes a plan', function () {
            $result = $this->planService->delete($this->plan->id, $this->user->id);
            
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('plans', [
                'id' => $this->plan->id,
            ]);
        });

        it('deletes plan without user filter', function () {
            $newPlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            $result = $this->planService->delete($newPlan->id);
            
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('plans', [
                'id' => $newPlan->id,
            ]);
        });

        it('throws exception for invalid plan access', function ($planId, $scenario) {
            if ($scenario === 'plan from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
                $planId = $otherPlan->id;
            }
            
            expect(fn() => $this->planService->delete($planId, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });
});
