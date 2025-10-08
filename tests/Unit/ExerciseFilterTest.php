<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Filters\ExerciseFilter;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('ExerciseFilter', function () {
    describe('apply', function () {
        it('applies search filter', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Bench Press',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $filter = new ExerciseFilter(['search' => 'push']);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('Push-ups');
        });

        it('applies user filter', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $otherUser = User::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'User Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Other User Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $otherUser->id,
            ]);

            $filter = new ExerciseFilter(['user_id' => $this->user->id]);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('User Exercise');
        });

        it('applies muscle group filter', function () {
            $muscleGroup1 = MuscleGroup::factory()->create(['name' => 'Chest']);
            $muscleGroup2 = MuscleGroup::factory()->create(['name' => 'Back']);
            
            Exercise::factory()->create([
                'name' => 'Chest Exercise',
                'muscle_group_id' => $muscleGroup1->id,
                'user_id' => $this->user->id,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Back Exercise',
                'muscle_group_id' => $muscleGroup2->id,
                'user_id' => $this->user->id,
            ]);

            $filter = new ExerciseFilter(['muscle_group_id' => $muscleGroup1->id]);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('Chest Exercise');
        });

        it('applies is_active filter', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Active Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => true,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Inactive Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => false,
            ]);

            $filter = new ExerciseFilter(['is_active' => true]);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('Active Exercise');
        });

        it('applies sorting', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Chest Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Back Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $filter = new ExerciseFilter(['sort_by' => 'name', 'sort_order' => 'desc']);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            $names = $result->pluck('name')->toArray();
            expect($names)->toBe(['Chest Exercise', 'Back Exercise']);
        });

        it('applies date range filter', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $oldExercise = Exercise::factory()->create([
                'name' => 'Old Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'created_at' => now()->subDays(10),
            ]);
            
            $newExercise = Exercise::factory()->create([
                'name' => 'New Exercise',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'created_at' => now()->subDays(2),
            ]);

            $filter = new ExerciseFilter([
                'date_from' => now()->subDays(5)->toDateString(),
                'date_to' => now()->toDateString(),
            ]);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->id)->toBe($newExercise->id);
        });

        it('applies multiple filters together', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Chest Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => true,
                'created_at' => now()->subDays(10),
            ]);
            
            Exercise::factory()->create([
                'name' => 'Chest Bench Press',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => true,
                'created_at' => now()->subDays(2),
            ]);
            
            Exercise::factory()->create([
                'name' => 'Back Pull-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => false,
                'created_at' => now()->subDays(2),
            ]);

            $filter = new ExerciseFilter([
                'search' => 'chest',
                'is_active' => true,
                'date_from' => now()->subDays(5)->toDateString(),
                'sort_by' => 'name',
                'sort_order' => 'asc',
            ]);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('Chest Bench Press');
        });

        it('handles empty filters', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            Exercise::factory()->count(3)->create([
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $filter = new ExerciseFilter([]);
            $query = Exercise::query();
            $filter->apply($query);

            $result = $query->get();

            expect($result)->toHaveCount(3);
        });
    });

    describe('getPaginationParams', function () {
        it('returns default pagination params', function () {
            $filter = new ExerciseFilter([]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 100]);
        });

        it('returns custom per_page', function () {
            $filter = new ExerciseFilter(['per_page' => 25]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 25]);
        });

        it('limits max per_page to 100', function () {
            $filter = new ExerciseFilter(['per_page' => 150]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 100]);
        });

        it('ensures minimum per_page of 1', function () {
            $filter = new ExerciseFilter(['per_page' => 0]);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 1]);
        });

        it('handles invalid per_page values', function () {
            $filter = new ExerciseFilter(['per_page' => 'invalid']);

            $params = $filter->getPaginationParams();

            expect($params)->toBe(['per_page' => 1]);
        });
    });
});
