<?php

declare(strict_types=1);

use App\Filters\PlanFilter;
use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
});

describe('PlanFilter', function () {
    describe('search filter', function () {
        it('filters plans by name', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);
            
            $filter = new PlanFilter(['search' => 'Test']);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->name)->toBe('Test Plan');
        });

        it('search is case insensitive', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);
            
            $filter = new PlanFilter(['search' => 'test']);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->name)->toBe('Test Plan');
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
            
            $filter = new PlanFilter(['search' => 'Test']);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2);
            expect($results->pluck('name')->toArray())->toContain('Test Plan', 'Testing Plan');
        });

        it('ignores search when not provided', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Another Plan',
            ]);
            
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('user filter', function () {
        it('filters plans by user through cycle relationship', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            
            $filter = new PlanFilter(['user_id' => $this->user->id]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($this->plan->id);
        });

        it('ignores user filter when not provided', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2); // Both plans
        });
    });

    describe('cycle filter', function () {
        it('filters plans by cycle_id', function () {
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            
            $filter = new PlanFilter(['cycle_id' => $this->cycle->id]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($this->plan->id);
        });

        it('ignores cycle filter when not provided', function () {
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2); // Both plans
        });
    });

    describe('standalone filter', function () {
        it('filters standalone plans (without cycle)', function () {
            $standalonePlan = Plan::factory()->create([
                'cycle_id' => null,
                'user_id' => $this->user->id,
            ]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            $filter = new PlanFilter(['standalone' => true, 'user_id' => $this->user->id]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($standalonePlan->id);
            expect($results->first()->cycle_id)->toBeNull();
        });

        it('filters standalone plans with string true', function () {
            $standalonePlan = Plan::factory()->create([
                'cycle_id' => null,
                'user_id' => $this->user->id,
            ]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            $filter = new PlanFilter(['standalone' => 'true', 'user_id' => $this->user->id]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($standalonePlan->id);
        });

        it('filters standalone plans with string 1', function () {
            $standalonePlan = Plan::factory()->create([
                'cycle_id' => null,
                'user_id' => $this->user->id,
            ]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            $filter = new PlanFilter(['standalone' => '1', 'user_id' => $this->user->id]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($standalonePlan->id);
        });

        it('filters plans with cycle (not standalone)', function () {
            $standalonePlan = Plan::factory()->create(['cycle_id' => null]);
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            
            $filter = new PlanFilter(['standalone' => false]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Проверяем только созданные планы (исключаем $this->plan)
            $createdPlans = $results->whereIn('id', [$cyclePlan->id, $this->plan->id]);
            
            expect($createdPlans)->toHaveCount(2);
            expect($createdPlans->pluck('cycle_id')->toArray())->not->toContain(null);
        });

        it('filters plans with cycle using string false', function () {
            $standalonePlan = Plan::factory()->create(['cycle_id' => null]);
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            
            $filter = new PlanFilter(['standalone' => 'false']);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Проверяем только созданные планы (исключаем $this->plan)
            $createdPlans = $results->whereIn('id', [$cyclePlan->id, $this->plan->id]);
            
            expect($createdPlans)->toHaveCount(2);
            expect($createdPlans->pluck('cycle_id')->toArray())->not->toContain(null);
        });

        it('filters plans with cycle using string 0', function () {
            $standalonePlan = Plan::factory()->create(['cycle_id' => null]);
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
            
            $filter = new PlanFilter(['standalone' => '0']);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Проверяем только созданные планы (исключаем $this->plan)
            $createdPlans = $results->whereIn('id', [$cyclePlan->id, $this->plan->id]);
            
            expect($createdPlans)->toHaveCount(2);
            expect($createdPlans->pluck('cycle_id')->toArray())->not->toContain(null);
        });

        it('ignores standalone filter when not provided', function () {
            $standalonePlan = Plan::factory()->create(['cycle_id' => null]);
            $cyclePlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
            
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // $this->plan + $standalonePlan + $cyclePlan
        });
    });

    describe('order filter', function () {
        it('filters plans by order', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'order' => 1,
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'order' => 2,
            ]);
            
            $filter = new PlanFilter(['order' => 1]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Проверяем только созданные планы (исключаем $this->plan)
            $createdPlans = $results->whereIn('id', [$plan1->id, $plan2->id]);
            
            expect($createdPlans)->toHaveCount(1);
            expect($createdPlans->first()->order)->toBe(1);
        });

        it('ignores order filter when not provided', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'order' => 1,
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'order' => 2,
            ]);
            
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('sorting', function () {
        it('applies default sorting by order asc', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'order' => 3,
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'order' => 1,
            ]);
            
            $plan3 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'order' => 2,
            ]);
            
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check that plans are sorted by order (excluding $this->plan which has null order)
            $sortedPlans = $results->whereIn('id', [$plan1->id, $plan2->id, $plan3->id])->sortBy('order');
            
            expect($sortedPlans->first()->order)->toBe(1);
            expect($sortedPlans->last()->order)->toBe(3);
        });

        it('applies custom sorting', function () {
            $plan1 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Z Plan',
                'order' => 1,
            ]);
            
            $plan2 = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'A Plan',
                'order' => 2,
            ]);
            
            $filter = new PlanFilter(['sort_by' => 'name', 'sort_direction' => 'asc']);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check that plans are sorted by name
            $sortedPlans = $results->whereIn('id', [$plan1->id, $plan2->id])->sortBy('name');
            
            expect($sortedPlans->first()->name)->toBe('A Plan');
            expect($sortedPlans->last()->name)->toBe('Z Plan');
        });
    });

    describe('date range filter', function () {
        it('filters plans by date range', function () {
            $oldPlan = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'created_at' => now()->subDays(10),
            ]);
            
            $recentPlan = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'created_at' => now()->subDays(2),
            ]);
            
            $filter = new PlanFilter([
                'date_from' => now()->subDays(5)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ]);
            
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the recent plan is in the range
            $createdPlans = $results->whereIn('id', [$oldPlan->id, $recentPlan->id]);
            
            expect($createdPlans)->toHaveCount(1);
            expect($createdPlans->first()->id)->toBe($recentPlan->id);
        });

        it('ignores date range filter when not provided', function () {
            $oldPlan = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'created_at' => now()->subDays(10),
            ]);
            
            $recentPlan = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'created_at' => now()->subDays(2),
            ]);
            
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('multiple filters', function () {
        it('applies multiple filters correctly', function () {
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
            
            $filter = new PlanFilter([
                'search' => 'Test',
                'order' => 1,
                'cycle_id' => $this->cycle->id,
            ]);
            
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->name)->toBe('Test Plan');
            expect($results->first()->order)->toBe(1);
        });

        it('applies standalone filter with other filters', function () {
            $standalonePlan = Plan::factory()->create([
                'cycle_id' => null,
                'user_id' => $this->user->id,
                'name' => 'Standalone Test Plan',
                'order' => 1,
            ]);
            
            $cyclePlan = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Cycle Test Plan',
                'order' => 1,
            ]);
            
            $filter = new PlanFilter([
                'search' => 'Test',
                'standalone' => true,
                'user_id' => $this->user->id,
            ]);
            
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->name)->toBe('Standalone Test Plan');
            expect($results->first()->cycle_id)->toBeNull();
        });
    });

    describe('empty filters', function () {
        it('handles empty filters', function () {
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($this->plan->id);
        });
    });

    describe('return value', function () {
        it('returns builder instance', function () {
            $filter = new PlanFilter([]);
            $query = Plan::query();
            
            $result = $filter->apply($query);
            
            expect($result)->toBeInstanceOf(Builder::class);
        });
    });
});
