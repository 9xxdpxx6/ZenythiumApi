<?php

declare(strict_types=1);

use App\Models\Cycle;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\Plan;
use App\Models\User;
use App\Services\ExerciseResolutionService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->service = new ExerciseResolutionService();
});

describe('ExerciseResolutionService', function () {
    describe('resolveOrCreateExercise', function () {
        it('creates new exercise if it does not exist', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exerciseData = [
                'name' => 'Жим лежа',
                'description' => 'Базовое упражнение',
                'muscle_group' => [
                    'id' => $muscleGroup->id,
                    'name' => $muscleGroup->name,
                ],
            ];

            $exercise = $this->service->resolveOrCreateExercise($exerciseData, $this->user->id);

            expect($exercise)->toBeInstanceOf(Exercise::class);
            expect($exercise->name)->toBe('Жим лежа');
            expect($exercise->user_id)->toBe($this->user->id);
            expect($exercise->muscle_group_id)->toBe($muscleGroup->id);
        });

        it('returns existing exercise if it exists', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $existingExercise = Exercise::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Жим лежа',
                'muscle_group_id' => $muscleGroup->id,
            ]);

            $exerciseData = [
                'name' => 'Жим лежа',
                'muscle_group' => [
                    'id' => $muscleGroup->id,
                    'name' => $muscleGroup->name,
                ],
            ];

            $exercise = $this->service->resolveOrCreateExercise($exerciseData, $this->user->id);

            expect($exercise->id)->toBe($existingExercise->id);
            expect(Exercise::where('user_id', $this->user->id)->where('name', 'Жим лежа')->count())->toBe(1);
        });

        it('adds muscle group name to exercise name if same name with different group exists', function () {
            $muscleGroup1 = MuscleGroup::factory()->create(['name' => 'Грудь']);
            $muscleGroup2 = MuscleGroup::factory()->create(['name' => 'Плечи']);
            
            Exercise::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Жим лежа',
                'muscle_group_id' => $muscleGroup1->id,
            ]);

            $exerciseData = [
                'name' => 'Жим лежа',
                'muscle_group' => [
                    'id' => $muscleGroup2->id,
                    'name' => $muscleGroup2->name,
                ],
            ];

            $exercise = $this->service->resolveOrCreateExercise($exerciseData, $this->user->id);

            expect($exercise->name)->toBe('Жим лежа (Плечи)');
            expect($exercise->muscle_group_id)->toBe($muscleGroup2->id);
        });

        it('supports old format with muscle_group_id', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            
            $exerciseData = [
                'name' => 'Жим лежа',
                'muscle_group_id' => $muscleGroup->id,
            ];

            $exercise = $this->service->resolveOrCreateExercise($exerciseData, $this->user->id);

            expect($exercise)->toBeInstanceOf(Exercise::class);
            expect($exercise->muscle_group_id)->toBe($muscleGroup->id);
        });

        it('uses preloaded exercises for optimization', function () {
            $muscleGroup = MuscleGroup::factory()->create();
            $existingExercise = Exercise::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Жим лежа',
                'muscle_group_id' => $muscleGroup->id,
            ]);

            $userExercises = Exercise::where('user_id', $this->user->id)->get();
            $muscleGroups = MuscleGroup::all();

            $exerciseData = [
                'name' => 'Жим лежа',
                'muscle_group' => [
                    'id' => $muscleGroup->id,
                    'name' => $muscleGroup->name,
                ],
            ];

            $exercise = $this->service->resolveOrCreateExercise(
                $exerciseData,
                $this->user->id,
                $userExercises,
                $muscleGroups
            );

            expect($exercise->id)->toBe($existingExercise->id);
        });
    });

    describe('resolveUniqueName', function () {
        it('returns base name if unique', function () {
            $name = $this->service->resolveUniqueName(
                Cycle::class,
                'Новый цикл',
                $this->user->id
            );

            expect($name)->toBe('Новый цикл');
        });

        it('adds counter if name exists', function () {
            Cycle::factory()->create([
                'user_id' => $this->user->id,
                'name' => 'Новый цикл',
            ]);

            $name = $this->service->resolveUniqueName(
                Cycle::class,
                'Новый цикл',
                $this->user->id
            );

            expect($name)->toBe('Новый цикл 1');
        });

        it('increments counter until unique name found', function () {
            Cycle::factory()->create(['user_id' => $this->user->id, 'name' => 'Новый цикл']);
            Cycle::factory()->create(['user_id' => $this->user->id, 'name' => 'Новый цикл 1']);
            Cycle::factory()->create(['user_id' => $this->user->id, 'name' => 'Новый цикл 2']);

            $name = $this->service->resolveUniqueName(
                Cycle::class,
                'Новый цикл',
                $this->user->id
            );

            expect($name)->toBe('Новый цикл 3');
        });

        it('uses preloaded names for optimization', function () {
            Cycle::factory()->create(['user_id' => $this->user->id, 'name' => 'Новый цикл']);
            Cycle::factory()->create(['user_id' => $this->user->id, 'name' => 'Новый цикл 1']);

            $existingNames = Cycle::where('user_id', $this->user->id)->pluck('name');

            $name = $this->service->resolveUniqueName(
                Cycle::class,
                'Новый цикл',
                $this->user->id,
                $existingNames
            );

            expect($name)->toBe('Новый цикл 2');
        });
    });
});

