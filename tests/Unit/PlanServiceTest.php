<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use App\Services\PlanService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    $this->planService = new PlanService();
});

test('can get all plans with pagination', function () {
    Plan::factory()->count(5)->create(['cycle_id' => $this->cycle->id]);
    
    $filters = ['user_id' => $this->user->id];
    $result = $this->planService->getAll($filters);
    
    expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
    expect($result->count())->toBe(6); // 5 new + 1 existing
    expect($result->items()[0])->toBeInstanceOf(Plan::class);
});

test('can get plan by id', function () {
    $plan = $this->planService->getById($this->plan->id, $this->user->id);
    
    expect($plan)->toBeInstanceOf(Plan::class);
    expect($plan->id)->toBe($this->plan->id);
    expect($plan->cycle)->toBeInstanceOf(Cycle::class);
});

test('can create a plan', function () {
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

test('can update a plan', function () {
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

test('can delete a plan', function () {
    $result = $this->planService->delete($this->plan->id, $this->user->id);
    
    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('plans', [
        'id' => $this->plan->id,
    ]);
});

test('cannot access plan from other user', function () {
    $otherUser = User::factory()->create();
    $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
    $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
    
    expect(fn() => $this->planService->getById($otherPlan->id, $this->user->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('cannot update plan from other user', function () {
    $otherUser = User::factory()->create();
    $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
    $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
    
    $data = ['name' => 'Updated Plan'];
    
    expect(fn() => $this->planService->update($otherPlan->id, $data, $this->user->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('cannot delete plan from other user', function () {
    $otherUser = User::factory()->create();
    $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
    $otherPlan = Plan::factory()->create(['cycle_id' => $otherCycle->id]);
    
    expect(fn() => $this->planService->delete($otherPlan->id, $this->user->id))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('can get plan without user filter', function () {
    $plan = $this->planService->getById($this->plan->id);
    
    expect($plan)->toBeInstanceOf(Plan::class);
    expect($plan->id)->toBe($this->plan->id);
});

test('can update plan without user filter', function () {
    $data = ['name' => 'Updated Plan'];
    
    $plan = $this->planService->update($this->plan->id, $data);
    
    expect($plan)->toBeInstanceOf(Plan::class);
    expect($plan->name)->toBe('Updated Plan');
});

test('can delete plan without user filter', function () {
    $newPlan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
    
    $result = $this->planService->delete($newPlan->id);
    
    expect($result)->toBeTrue();
    $this->assertDatabaseMissing('plans', [
        'id' => $newPlan->id,
    ]);
});
