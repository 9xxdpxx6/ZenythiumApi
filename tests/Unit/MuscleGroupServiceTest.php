<?php

declare(strict_types=1);

use App\Models\MuscleGroup;
use App\Models\User;
use App\Services\MuscleGroupService;

dataset('exception_scenarios', [
    'non_existent' => [PHP_INT_MAX, 'non-existent muscle group'],
]);

beforeEach(function () {
    $this->service = new MuscleGroupService;
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
            // Создаем 25 уникальных muscle groups с явно указанными именами
            for ($i = 1; $i <= 25; $i++) {
                MuscleGroup::factory()->create(['name' => "Muscle Group {$i}"]);
            }

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

        it('returns null for non-existent muscle group', function ($muscleGroupId, $scenario) {
            $result = $this->service->getById($muscleGroupId);
            expect($result)->toBeNull();
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

});
