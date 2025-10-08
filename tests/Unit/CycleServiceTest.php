<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\User;
use App\Services\CycleService;

dataset('exception_scenarios', [
    'non_existent' => [999999, 'non-existent cycle'],
    'other_user' => [null, 'cycle from other user'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->cycleService = new CycleService();
});

describe('CycleService', function () {
    describe('getAll', function () {
        it('returns paginated cycles with user filter', function () {
            Cycle::factory()->count(5)->create(['user_id' => $this->user->id]);
            
            $filters = ['user_id' => $this->user->id];
            $result = $this->cycleService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(6); // 5 new + 1 existing
            expect($result->items()[0])->toBeInstanceOf(Cycle::class);
        });

        it('returns empty paginator when user_id is null', function () {
            $filters = ['user_id' => null];
            $result = $this->cycleService->getAll($filters);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
            expect($result->total())->toBe(0);
        });

        it('returns empty paginator when user_id is not provided', function () {
            $result = $this->cycleService->getAll([]);
            
            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(0);
            expect($result->total())->toBe(0);
        });

        it('applies filters correctly', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Cycle',
                'weeks' => 4,
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Another Cycle',
                'weeks' => 8,
            ]);

            $filters = [
                'user_id' => $this->user->id,
                'search' => 'Test',
            ];
            
            $result = $this->cycleService->getAll($filters);
            
            expect($result->count())->toBe(1);
            expect($result->items()[0]->name)->toBe('Test Cycle');
        });
    });

    describe('getById', function () {
        it('returns cycle by id', function () {
            $cycle = $this->cycleService->getById($this->cycle->id, $this->user->id);
            
            expect($cycle)->toBeInstanceOf(Cycle::class);
            expect($cycle->id)->toBe($this->cycle->id);
        });

        it('returns cycle without user filter', function () {
            $cycle = $this->cycleService->getById($this->cycle->id);
            
            expect($cycle)->toBeInstanceOf(Cycle::class);
            expect($cycle->id)->toBe($this->cycle->id);
        });

        it('throws exception for invalid cycle access', function ($cycleId, $scenario) {
            if ($scenario === 'cycle from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $cycleId = $otherCycle->id;
            }
            
            expect(fn() => $this->cycleService->getById($cycleId, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });

    describe('create', function () {
        it('creates a new cycle', function () {
            $data = [
                'user_id' => $this->user->id,
                'name' => 'New Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $cycle = $this->cycleService->create($data);
            
            expect($cycle)->toBeInstanceOf(Cycle::class);
            expect($cycle->user_id)->toBe($this->user->id);
            expect($cycle->name)->toBe('New Cycle');
            expect($cycle->weeks)->toBe(4);
            
            $this->assertDatabaseHas('cycles', [
                'user_id' => $this->user->id,
                'name' => 'New Cycle',
                'weeks' => 4,
            ]);
        });

        it('creates cycle with minimal required data', function () {
            $data = [
                'user_id' => $this->user->id,
                'name' => 'Minimal Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 1,
            ];
            
            $cycle = $this->cycleService->create($data);
            
            expect($cycle)->toBeInstanceOf(Cycle::class);
            expect($cycle->name)->toBe('Minimal Cycle');
            expect($cycle->weeks)->toBe(1);
        });
    });

    describe('update', function () {
        it('updates a cycle', function () {
            $data = [
                'name' => 'Updated Cycle',
                'weeks' => 8,
            ];
            
            $cycle = $this->cycleService->update($this->cycle->id, $data, $this->user->id);
            
            expect($cycle)->toBeInstanceOf(Cycle::class);
            expect($cycle->id)->toBe($this->cycle->id);
            expect($cycle->name)->toBe('Updated Cycle');
            expect($cycle->weeks)->toBe(8);
            
            $this->assertDatabaseHas('cycles', [
                'id' => $this->cycle->id,
                'name' => 'Updated Cycle',
                'weeks' => 8,
            ]);
        });

        it('updates cycle without user filter', function () {
            $data = ['name' => 'Updated Cycle'];
            
            $cycle = $this->cycleService->update($this->cycle->id, $data);
            
            expect($cycle)->toBeInstanceOf(Cycle::class);
            expect($cycle->name)->toBe('Updated Cycle');
        });

        it('throws exception for invalid cycle access', function ($cycleId, $scenario) {
            $data = ['name' => 'Updated Cycle'];
            
            if ($scenario === 'cycle from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $cycleId = $otherCycle->id;
            }
            
            expect(fn() => $this->cycleService->update($cycleId, $data, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });

    describe('delete', function () {
        it('deletes a cycle', function () {
            $result = $this->cycleService->delete($this->cycle->id, $this->user->id);
            
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('cycles', [
                'id' => $this->cycle->id,
            ]);
        });

        it('deletes cycle without user filter', function () {
            $newCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            $result = $this->cycleService->delete($newCycle->id);
            
            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('cycles', [
                'id' => $newCycle->id,
            ]);
        });

        it('throws exception for invalid cycle access', function ($cycleId, $scenario) {
            if ($scenario === 'cycle from other user') {
                $otherUser = User::factory()->create();
                $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
                $cycleId = $otherCycle->id;
            }
            
            expect(fn() => $this->cycleService->delete($cycleId, $this->user->id))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });
});
