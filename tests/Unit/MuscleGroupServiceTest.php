<?php

declare(strict_types=1);

use App\Models\MuscleGroup;
use App\Models\User;
use App\Services\MuscleGroupService;

dataset('exception_scenarios', [
    'non_existent' => [999999, 'non-existent muscle group'],
]);

beforeEach(function () {
    $this->service = new MuscleGroupService();
    $this->user = User::factory()->create();
});

describe('MuscleGroupService', function () {
    describe('getAll', function () {
        it('returns all muscle groups without filters', function () {
            MuscleGroup::factory()->count(3)->create();

            $result = $this->service->getAll();

            expect($result)->toHaveCount(3);
        });

        it('applies search filter', function () {
            MuscleGroup::factory()->create(['name' => 'Chest']);
            MuscleGroup::factory()->create(['name' => 'Back']);
            MuscleGroup::factory()->create(['name' => 'Legs']);

            $result = $this->service->getAll(['search' => 'chest']);

            expect($result)->toHaveCount(1);
            expect($result->first()->name)->toBe('Chest');
        });

        it('applies user filter for exercises count', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);
            
            // Create exercises for the user
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

            $result = $this->service->getAll(['user_id' => $this->user->id]);

            expect($result)->toHaveCount(1);
            expect($result->first()->exercises_count)->toBe(2);
        });

        it('returns paginated results when per_page is specified', function () {
            MuscleGroup::factory()->count(25)->create();

            $result = $this->service->getAll(['per_page' => 10]);

            expect($result)->toBeInstanceOf(\Illuminate\Pagination\LengthAwarePaginator::class);
            expect($result->count())->toBe(10);
            expect($result->perPage())->toBe(10);
        });

        it('applies sorting', function () {
            MuscleGroup::factory()->create(['name' => 'Chest']);
            MuscleGroup::factory()->create(['name' => 'Back']);
            MuscleGroup::factory()->create(['name' => 'Legs']);

            $result = $this->service->getAll(['sort_by' => 'name', 'sort_order' => 'desc']);

            $names = $result->pluck('name')->toArray();
            expect($names)->toBe(['Legs', 'Chest', 'Back']);
        });
    });

    describe('getById', function () {
        it('returns muscle group by id', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);

            $result = $this->service->getById($muscleGroup->id);

            expect($result->id)->toBe($muscleGroup->id);
            expect($result->name)->toBe('Chest');
        });

        it('throws exception for non-existent muscle group', function ($muscleGroupId, $scenario) {
            expect(fn() => $this->service->getById($muscleGroupId))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');

        it('applies user filter for exercises count', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);
            
            $muscleGroup->exercises()->create([
                'name' => 'Push-ups',
                'description' => 'Basic push-ups',
                'user_id' => $this->user->id,
            ]);

            $result = $this->service->getById($muscleGroup->id, $this->user->id);

            expect($result->exercises_count)->toBe(1);
        });
    });

    describe('create', function () {
        it('creates a new muscle group', function () {
            $data = ['name' => 'Chest'];

            $result = $this->service->create($data);

            expect($result->name)->toBe('Chest');
            $this->assertDatabaseHas('muscle_groups', ['name' => 'Chest']);
        });
    });

    describe('update', function () {
        it('updates a muscle group', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);
            $data = ['name' => 'Chest Updated'];

            $result = $this->service->update($muscleGroup->id, $data);

            expect($result->name)->toBe('Chest Updated');
            $this->assertDatabaseHas('muscle_groups', [
                'id' => $muscleGroup->id,
                'name' => 'Chest Updated',
            ]);
        });

        it('throws exception for non-existent muscle group', function ($muscleGroupId, $scenario) {
            $data = ['name' => 'Test Muscle Group'];
            
            expect(fn() => $this->service->update($muscleGroupId, $data))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });

    describe('delete', function () {
        it('deletes a muscle group', function () {
            $muscleGroup = MuscleGroup::factory()->create(['name' => 'Chest']);

            $result = $this->service->delete($muscleGroup->id);

            expect($result)->toBeTrue();
            $this->assertDatabaseMissing('muscle_groups', ['id' => $muscleGroup->id]);
        });

        it('throws exception for non-existent muscle group', function ($muscleGroupId, $scenario) {
            expect(fn() => $this->service->delete($muscleGroupId))
                ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        })->with('exception_scenarios');
    });
});
