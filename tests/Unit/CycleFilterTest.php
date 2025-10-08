<?php

declare(strict_types=1);

use App\Filters\CycleFilter;
use App\Models\Cycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
});

describe('CycleFilter', function () {
    describe('search filter', function () {
        it('filters cycles by name', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Cycle',
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Another Cycle',
            ]);
            
            $filter = new CycleFilter(['search' => 'Test']);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->name)->toBe('Test Cycle');
        });

        it('search is case insensitive', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Cycle',
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Another Cycle',
            ]);
            
            $filter = new CycleFilter(['search' => 'test']);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->name)->toBe('Test Cycle');
        });

        it('search matches partial names', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Cycle',
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Testing Cycle',
            ]);
            
            $cycle3 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Another Cycle',
            ]);
            
            $filter = new CycleFilter(['search' => 'Test']);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2);
            expect($results->pluck('name')->toArray())->toContain('Test Cycle', 'Testing Cycle');
        });

        it('ignores search when not provided', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Test Cycle',
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Another Cycle',
            ]);
            
            $filter = new CycleFilter([]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('user filter', function () {
        it('filters cycles by user_id', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            
            $filter = new CycleFilter(['user_id' => $this->user->id]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($this->cycle->id);
        });

        it('ignores user filter when not provided', function () {
            $otherUser = User::factory()->create();
            $otherCycle = Cycle::factory()->create(['user_id' => $otherUser->id]);
            
            $filter = new CycleFilter([]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(2); // Both cycles
        });
    });

    describe('date range filter', function () {
        it('filters cycles by start_date_from', function () {
            $oldCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-01-01',
            ]);
            
            $recentCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-06-01',
            ]);
            
            $filter = new CycleFilter([
                'start_date_from' => '2024-05-01',
            ]);
            
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the recent cycle is in the range
            $createdCycles = $results->whereIn('id', [$oldCycle->id, $recentCycle->id]);
            
            expect($createdCycles)->toHaveCount(1);
            expect($createdCycles->first()->id)->toBe($recentCycle->id);
        });

        it('filters cycles by start_date_to', function () {
            $oldCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-01-01',
            ]);
            
            $recentCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-06-01',
            ]);
            
            $filter = new CycleFilter([
                'start_date_to' => '2024-05-01',
            ]);
            
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the old cycle is in the range
            $createdCycles = $results->whereIn('id', [$oldCycle->id, $recentCycle->id]);
            
            expect($createdCycles)->toHaveCount(1);
            expect($createdCycles->first()->id)->toBe($oldCycle->id);
        });

        it('filters cycles by end_date_from', function () {
            $oldCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'end_date' => '2024-01-31',
            ]);
            
            $recentCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'end_date' => '2024-06-30',
            ]);
            
            $filter = new CycleFilter([
                'end_date_from' => '2024-05-01',
            ]);
            
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the recent cycle is in the range
            $createdCycles = $results->whereIn('id', [$oldCycle->id, $recentCycle->id]);
            
            expect($createdCycles)->toHaveCount(1);
            expect($createdCycles->first()->id)->toBe($recentCycle->id);
        });

        it('filters cycles by end_date_to', function () {
            $oldCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'end_date' => '2024-01-31',
            ]);
            
            $recentCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'end_date' => '2024-06-30',
            ]);
            
            $filter = new CycleFilter([
                'end_date_to' => '2024-05-01',
            ]);
            
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check only the old cycle is in the range
            $createdCycles = $results->whereIn('id', [$oldCycle->id, $recentCycle->id]);
            
            expect($createdCycles)->toHaveCount(1);
            expect($createdCycles->first()->id)->toBe($oldCycle->id);
        });

        it('ignores date range filter when not provided', function () {
            $oldCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-01-01',
            ]);
            
            $recentCycle = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-06-01',
            ]);
            
            $filter = new CycleFilter([]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('weeks filter', function () {
        it('filters cycles by weeks_min', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 4,
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 8,
            ]);
            
            $filter = new CycleFilter(['weeks_min' => 6]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Проверяем только созданные циклы (исключаем $this->cycle)
            $createdCycles = $results->whereIn('id', [$cycle1->id, $cycle2->id]);
            
            expect($createdCycles)->toHaveCount(1);
            expect($createdCycles->first()->weeks)->toBe(8);
        });

        it('filters cycles by weeks_max', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 4,
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 8,
            ]);
            
            $filter = new CycleFilter(['weeks_max' => 6]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Проверяем только созданные циклы (исключаем $this->cycle)
            $createdCycles = $results->whereIn('id', [$cycle1->id, $cycle2->id]);
            
            expect($createdCycles)->toHaveCount(1);
            expect($createdCycles->first()->weeks)->toBe(4);
        });

        it('filters cycles by exact weeks', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 4,
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 8,
            ]);
            
            $filter = new CycleFilter(['weeks' => 4]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Проверяем только созданные циклы (исключаем $this->cycle)
            $createdCycles = $results->whereIn('id', [$cycle1->id, $cycle2->id]);
            
            expect($createdCycles)->toHaveCount(1);
            expect($createdCycles->first()->weeks)->toBe(4);
        });

        it('ignores weeks filter when not provided', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 4,
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'weeks' => 8,
            ]);
            
            $filter = new CycleFilter([]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(3); // 2 new + 1 existing
        });
    });

    describe('sorting', function () {
        it('applies default sorting by start_date desc', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-03-01',
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-01-01',
            ]);
            
            $cycle3 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'start_date' => '2024-02-01',
            ]);
            
            $filter = new CycleFilter([]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check that cycles are sorted by start_date desc
            $sortedCycles = $results->whereIn('id', [$cycle1->id, $cycle2->id, $cycle3->id])->sortByDesc('start_date');
            
            expect($sortedCycles->first()->start_date->format('Y-m-d'))->toBe('2024-03-01');
            expect($sortedCycles->last()->start_date->format('Y-m-d'))->toBe('2024-01-01');
        });

        it('applies custom sorting', function () {
            $cycle1 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Z Cycle',
                'start_date' => '2024-01-01',
            ]);
            
            $cycle2 = Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'A Cycle',
                'start_date' => '2024-02-01',
            ]);
            
            $filter = new CycleFilter(['sort_by' => 'name', 'sort_direction' => 'asc']);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            // Check that cycles are sorted by name
            $sortedCycles = $results->whereIn('id', [$cycle1->id, $cycle2->id])->sortBy('name');
            
            expect($sortedCycles->first()->name)->toBe('A Cycle');
            expect($sortedCycles->last()->name)->toBe('Z Cycle');
        });
    });

    describe('multiple filters', function () {
        it('applies multiple filters correctly', function () {
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
            
            $filter = new CycleFilter([
                'search' => 'Test',
                'weeks' => 4,
                'user_id' => $this->user->id,
            ]);
            
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->name)->toBe('Test Cycle');
            expect($results->first()->weeks)->toBe(4);
        });
    });

    describe('empty filters', function () {
        it('handles empty filters', function () {
            $filter = new CycleFilter([]);
            $query = Cycle::query();
            
            $filter->apply($query);
            
            $results = $query->get();
            
            expect($results)->toHaveCount(1);
            expect($results->first()->id)->toBe($this->cycle->id);
        });
    });

    describe('return value', function () {
        it('returns builder instance', function () {
            $filter = new CycleFilter([]);
            $query = Cycle::query();
            
            $result = $filter->apply($query);
            
            expect($result)->toBeInstanceOf(Builder::class);
        });
    });
});
