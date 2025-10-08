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

test('plan filter applies search filter', function () {
    $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Test Plan']);
    $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Another Plan']);
    
    $filter = new PlanFilter(['search' => 'Test']);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Test Plan');
});

test('plan filter applies user filter', function () {
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

test('plan filter applies cycle filter', function () {
    $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
    
    $filter = new PlanFilter(['cycle_id' => $this->cycle->id]);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->plan->id);
});

test('plan filter applies order filter', function () {
    $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'order' => 1]);
    $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'order' => 2]);
    
    $filter = new PlanFilter(['order' => 1]);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->order)->toBe(1);
});

test('plan filter applies sorting by order asc', function () {
    $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'order' => 3]);
    $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'order' => 1]);
    $plan3 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'order' => 2]);
    
    $filter = new PlanFilter([]);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results->first()->order)->toBe(1);
    expect($results->last()->order)->toBe(3);
});

test('plan filter applies custom sorting', function () {
    $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'order' => 3]);
    $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'order' => 1]);
    
    $filter = new PlanFilter(['sort_by' => 'name', 'sort_direction' => 'desc']);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(2);
});

test('plan filter applies date range filter', function () {
    $oldPlan = Plan::factory()->create([
        'cycle_id' => $this->cycle->id,
        'created_at' => now()->subDays(10)
    ]);
    
    $filter = new PlanFilter([
        'date_from' => now()->subDays(5)->format('Y-m-d'),
        'date_to' => now()->format('Y-m-d')
    ]);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->plan->id);
});

test('plan filter handles empty filters', function () {
    $filter = new PlanFilter([]);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($this->plan->id);
});

test('plan filter handles multiple filters', function () {
    $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Test Plan', 'order' => 1]);
    $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Another Plan', 'order' => 2]);
    
    $filter = new PlanFilter([
        'search' => 'Test',
        'order' => 1,
        'cycle_id' => $this->cycle->id
    ]);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Test Plan');
    expect($results->first()->order)->toBe(1);
});

test('plan filter search is case insensitive', function () {
    $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Test Plan']);
    $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Another Plan']);
    
    $filter = new PlanFilter(['search' => 'test']);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(1);
    expect($results->first()->name)->toBe('Test Plan');
});

test('plan filter search matches partial names', function () {
    $plan1 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Test Plan']);
    $plan2 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Testing Plan']);
    $plan3 = Plan::factory()->create(['cycle_id' => $this->cycle->id, 'name' => 'Another Plan']);
    
    $filter = new PlanFilter(['search' => 'Test']);
    $query = Plan::query();
    
    $filter->apply($query);
    
    $results = $query->get();
    
    expect($results)->toHaveCount(2);
    expect($results->pluck('name')->toArray())->toContain('Test Plan', 'Testing Plan');
});

test('plan filter returns builder instance', function () {
    $filter = new PlanFilter([]);
    $query = Plan::query();
    
    $result = $filter->apply($query);
    
    expect($result)->toBeInstanceOf(Builder::class);
});
