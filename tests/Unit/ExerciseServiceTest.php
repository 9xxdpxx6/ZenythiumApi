<?php

declare(strict_types=1);

use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Services\ExerciseService;

dataset('exception_scenarios', [
    'non_existent' => [PHP_INT_MAX, 'non-existent exercise'],
    'other_user' => [null, 'exercise from other user'],
]);

beforeEach(function () {
    $this->service = new ExerciseService();
    $this->user = User::factory()->create();
});

describe('ExerciseService', function () {
    describe('getAll', function () {
        it('returns all exercises for user without filters', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            Exercise::factory()->count(3)->create([
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $result = $this->service->getAll(['user_id' => $this->user->id]);

            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(3);
        });

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

            $result = $this->service->getAll([
                'user_id' => $this->user->id,
                'search' => 'push'
            ]);

            expect($result->count())->toBe(1);
            expect($result->first()->name)->toBe('Push-ups');
        });

        it('applies muscle group filter', function () {
            $muscleGroup1 = MuscleGroup::factory()->create(['name' => 'Chest']);
            $muscleGroup2 = MuscleGroup::factory()->create(['name' => 'Back']);
            
            Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup1->id,
                'user_id' => $this->user->id,
            ]);
            
            Exercise::factory()->create([
                'name' => 'Pull-ups',
                'muscle_group_id' => $muscleGroup2->id,
                'user_id' => $this->user->id,
            ]);

            $result = $this->service->getAll([
                'user_id' => $this->user->id,
                'muscle_group_id' => $muscleGroup1->id
            ]);

            expect($result->count())->toBe(1);
            expect($result->first()->name)->toBe('Push-ups');
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

            $result = $this->service->getAll([
                'user_id' => $this->user->id,
                'is_active' => true
            ]);

            expect($result->count())->toBe(1);
            expect($result->first()->name)->toBe('Active Exercise');
        });

        it('returns paginated results when per_page is specified', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            Exercise::factory()->count(25)->create([
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $result = $this->service->getAll([
                'user_id' => $this->user->id,
                'per_page' => 10
            ]);

            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(10);
            expect($result->perPage())->toBe(10);
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

            $result = $this->service->getAll([
                'user_id' => $this->user->id,
                'sort_by' => 'name',
                'sort_order' => 'desc'
            ]);

            $names = $result->pluck('name')->toArray();
            expect($names)->toBe(['Chest Exercise', 'Back Exercise']);
        });
    });

    describe('getById', function () {
        it('returns exercise by id', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $result = $this->service->getById($exercise->id, $this->user->id);

            expect($result->id)->toBe($exercise->id);
            expect($result->name)->toBe('Push-ups');
        });

        it('throws exception for invalid exercise access', function ($exerciseId, $scenario) {
            if ($scenario === 'exercise from other user') {
                $muscleGroup = MuscleGroup::factory()->create();
                $otherUser = User::factory()->create();
                
                $exercise = Exercise::factory()->create([
                    'muscle_group_id' => $muscleGroup->id,
                    'user_id' => $otherUser->id,
                ]);
                
                $exerciseId = $exercise->id;
            }
            
            $result = $this->service->getById($exerciseId, $this->user->id);
            expect($result)->toBeNull();
        })->with('exception_scenarios');
    });

    describe('create', function () {
        it('creates a new exercise', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $data = [
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
                'is_active' => true,
            ];

            $result = $this->service->create($data);

            expect($result->name)->toBe('Push-ups');
            expect($result->user_id)->toBe($this->user->id);
            $this->assertDatabaseHas('exercises', ['name' => 'Push-ups']);
        });
    });

    describe('update', function () {
        it('updates an exercise', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);
            
            $data = [
                'name' => 'Push-ups Updated',
                'description' => 'Updated description',
                'muscle_group_id' => $muscleGroup->id,
                'is_active' => false,
            ];

            $result = $this->service->update($exercise->id, $data, $this->user->id);

            expect($result->name)->toBe('Push-ups Updated');
            expect($result->is_active)->toBeFalse();
            
            $this->assertDatabaseHas('exercises', [
                'id' => $exercise->id,
                'name' => 'Push-ups Updated',
                'is_active' => false,
            ]);
        });

        it('throws exception for invalid exercise access', function ($exerciseId, $scenario) {
            $data = ['name' => 'Test Exercise'];
            
            if ($scenario === 'exercise from other user') {
                $muscleGroup = MuscleGroup::factory()->create();
                $otherUser = User::factory()->create();
                
                $exercise = Exercise::factory()->create([
                    'muscle_group_id' => $muscleGroup->id,
                    'user_id' => $otherUser->id,
                ]);
                
                $exerciseId = $exercise->id;
            }
            
            $result = $this->service->update($exerciseId, $data, $this->user->id);
            expect($result)->toBeNull();
        })->with('exception_scenarios');
    });

    describe('delete', function () {
        it('deletes an exercise', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exercise = Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $result = $this->service->delete($exercise->id, $this->user->id);

            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('exercises', ['id' => $exercise->id]);
        });

        it('throws exception for invalid exercise access', function ($exerciseId, $scenario) {
            if ($scenario === 'exercise from other user') {
                $muscleGroup = MuscleGroup::factory()->create();
                $otherUser = User::factory()->create();
                
                $exercise = Exercise::factory()->create([
                    'muscle_group_id' => $muscleGroup->id,
                    'user_id' => $otherUser->id,
                ]);
                
                $exerciseId = $exercise->id;
            }
            
            $result = $this->service->delete($exerciseId, $this->user->id);
            expect($result)->toBeFalse();
        })->with('exception_scenarios');
    });
});
