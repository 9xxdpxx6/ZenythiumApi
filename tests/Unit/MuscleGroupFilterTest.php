<?php

declare(strict_types=1);

use App\Models\MuscleGroup;
use App\Models\User;
use App\Filters\MuscleGroupFilter;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('MuscleGroupFilter', function () {
    describe('apply', function () {
        it('applies search filter', function () {
            MuscleGroup::factory()->create(['name' => 'Chest']);
            MuscleGroup::factory()->create(['name' => 'Back']);
            MuscleGroup::factory()->create(['name' => 'Legs']);

            $filter = new MuscleGroupFilter(['search' => 'chest']);
            $query = MuscleGroup::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('Chest');
        });

        it('applies user filter for exercises count', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);
            
            $muscleGroup->exercises()->create([
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'user_id' => $this->user->id,
            ]);
            
            $muscleGroup->exercises()->create([
                'name' => 'Bench Press',
                'description' => 'Bench press exercise',
                'user_id' => $this->user->id,
            ]);

            $filter = new MuscleGroupFilter(['user_id' => $this->user->id]);
            $query = MuscleGroup::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->exercises_count)->toBe(2);
        });

        it('applies sorting', function () {
            MuscleGroup::factory()->create(['name' => 'Chest']);
            MuscleGroup::factory()->create(['name' => 'Back']);
            MuscleGroup::factory()->create(['name' => 'Legs']);

            $filter = new MuscleGroupFilter(['sort_by' => 'name', 'sort_order' => 'desc']);
            $query = MuscleGroup::query();
            $filter->apply($query);

            $result = $query->get();

            $names = $result->pluck('name')->toArray();
            expect($names)->toBe(['Legs', 'Chest', 'Back']);
        });

        it('applies date range filter', function () {
            $oldGroup = MuscleGroup::factory()->create(['created_at' => now()->subDays(10)]);
            $newGroup = MuscleGroup::factory()->create(['created_at' => now()->subDays(2)]);

            $filter = new MuscleGroupFilter([
                'date_from' => now()->subDays(5)->toDateString(),
                'date_to' => now()->toDateString(),
            ]);
            $query = MuscleGroup::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->id)->toBe($newGroup->id);
        });

        it('applies multiple filters together', function () {
            MuscleGroup::factory()->create(['name' => 'Chest', 'created_at' => now()->subDays(10)]);
            MuscleGroup::factory()->create(['name' => 'Chest Back', 'created_at' => now()->subDays(2)]);
            MuscleGroup::factory()->create(['name' => 'Back', 'created_at' => now()->subDays(2)]);

            $filter = new MuscleGroupFilter([
                'search' => 'chest',
                'date_from' => now()->subDays(5)->toDateString(),
                'sort_by' => 'name',
                'sort_order' => 'asc',
            ]);
            $query = MuscleGroup::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('Chest Back');
        });

        it('handles empty filters', function () {
            MuscleGroup::factory()->count(3)->create();

            $filter = new MuscleGroupFilter([]);
            $query = MuscleGroup::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(3);
        });
    });

    describe('getPaginationParams', function () {
        it('returns default pagination params', function () {
            $filter = new MuscleGroupFilter([]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 15]);
        });

        it('returns custom per_page', function () {
            $filter = new MuscleGroupFilter(['per_page' => 25]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 25]);
        });

        it('limits max per_page to 100', function () {
            $filter = new MuscleGroupFilter(['per_page' => 150]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 100]);
        });

        it('ensures minimum per_page of 1', function () {
            $filter = new MuscleGroupFilter(['per_page' => 0]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 1]);
        });

        it('handles invalid per_page values', function () {
            $filter = new MuscleGroupFilter(['per_page' => 'invalid']);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 1]);
        });
    });
});
