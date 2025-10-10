<?php

declare(strict_types=1);

use App\Http\Requests\ExerciseRequest;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

dataset('required_fields', [
    'name' => ['name'],
    'muscle_group_id' => ['muscle_group_id'],
]);

dataset('invalid_muscle_group_ids', [
    'non_existent' => [PHP_INT_MAX],
    'non_integer' => ['not-a-number'],
]);

dataset('valid_muscle_group_ids', [
    'integer' => [1],
    'string_number' => ['1'],
]);

dataset('invalid_boolean_values', [
    'string' => ['true'],
    'array' => [[]],
]);

dataset('valid_boolean_values', [
    'true' => [true],
    'false' => [false],
    'string_true' => ['1'],
    'string_false' => ['0'],
]);

describe('ExerciseRequest', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->muscleGroup = MuscleGroup::factory()->create();
    });

    describe('validation rules', function () {
        it('validates required field', function ($field) {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Exercise',
                'muscle_group_id' => $this->muscleGroup->id,
            ];
            
            unset($data[$field]);
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has($field))->toBeTrue();
        })->with('required_fields');

        it('validates name is string', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 123,
                'muscle_group_id' => $this->muscleGroup->id,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('validates name max length', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $longName = str_repeat('a', 256);
            $validator = Validator::make([
                'name' => $longName,
                'muscle_group_id' => $this->muscleGroup->id,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('validates muscle_group_id with invalid values', function ($muscleGroupId) {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $muscleGroupId,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('muscle_group_id'))->toBeTrue();
        })->with('invalid_muscle_group_ids');

        it('validates muscle_group_id with valid values', function ($muscleGroupId) {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $muscleGroupId,
            ], $request->rules());

            expect($validator->passes())->toBeTrue();
        })->with('valid_muscle_group_ids');

        it('validates unique name per user for creation', function () {
            Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $this->muscleGroup->id,
                'user_id' => $this->user->id,
            ]);

            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Push-ups',
                'muscle_group_id' => $this->muscleGroup->id,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('allows same name for different users', function () {
            $otherUser = User::factory()->create();
            
            Exercise::factory()->create([
                'name' => 'Push-ups',
                'muscle_group_id' => $this->muscleGroup->id,
                'user_id' => $otherUser->id,
            ]);

            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Push-ups',
                'muscle_group_id' => $this->muscleGroup->id,
            ], $request->rules());

            expect($validator->fails())->toBeFalse();
        });

        it('validates description is string when provided', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $this->muscleGroup->id,
                'description' => 123,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('description'))->toBeTrue();
        });

        it('validates description max length', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $longDescription = str_repeat('a', 1001);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $this->muscleGroup->id,
                'description' => $longDescription,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('description'))->toBeTrue();
        });

        it('validates is_active with invalid values', function ($isActive) {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $this->muscleGroup->id,
                'is_active' => $isActive,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('is_active'))->toBeTrue();
        })->with('invalid_boolean_values');

        it('validates is_active with valid values', function ($isActive) {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $this->muscleGroup->id,
                'is_active' => $isActive,
            ], $request->rules());

            expect($validator->passes())->toBeTrue();
        })->with('valid_boolean_values');

        it('passes validation with valid data', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'description' => 'Test description',
                'muscle_group_id' => $this->muscleGroup->id,
                'is_active' => true,
            ], $request->rules());

            expect($validator->fails())->toBeFalse();
        });

        it('passes validation with minimal required data', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $this->muscleGroup->id,
            ], $request->rules());

            expect($validator->fails())->toBeFalse();
        });
    });

    describe('custom messages', function () {
        it('returns custom validation messages', function () {
            $request = new ExerciseRequest();
            $messages = $request->messages();

            expect($messages)->toHaveKey('name.required');
            expect($messages)->toHaveKey('name.string');
            expect($messages)->toHaveKey('name.max');
            expect($messages)->toHaveKey('name.unique');
            expect($messages)->toHaveKey('muscle_group_id.required');
            expect($messages)->toHaveKey('muscle_group_id.integer');
            expect($messages)->toHaveKey('muscle_group_id.exists');
            expect($messages)->toHaveKey('description.string');
            expect($messages)->toHaveKey('description.max');
            expect($messages)->toHaveKey('is_active.boolean');

            expect($messages['name.required'])->not->toBeEmpty();
            expect($messages['name.string'])->not->toBeEmpty();
            expect($messages['name.max'])->not->toBeEmpty();
            expect($messages['name.unique'])->not->toBeEmpty();
            expect($messages['muscle_group_id.required'])->not->toBeEmpty();
            expect($messages['muscle_group_id.integer'])->not->toBeEmpty();
            expect($messages['muscle_group_id.exists'])->not->toBeEmpty();
            expect($messages['description.string'])->not->toBeEmpty();
            expect($messages['description.max'])->not->toBeEmpty();
            expect($messages['is_active.boolean'])->not->toBeEmpty();
        });
    });

    describe('authorization', function () {
        it('allows all requests', function () {
            $request = new ExerciseRequest();

            expect($request->authorize())->toBeTrue();
        });
    });
});
