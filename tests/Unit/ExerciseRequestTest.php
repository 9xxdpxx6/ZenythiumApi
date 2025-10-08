<?php

declare(strict_types=1);

use App\Http\Requests\ExerciseRequest;
use App\Models\Exercise;
use App\Models\MuscleGroup;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

describe('ExerciseRequest', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->muscleGroup = MuscleGroup::factory()->create();
    });

    describe('validation rules', function () {
        it('validates required name field', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('validates required muscle_group_id field', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make(['name' => 'Test Exercise'], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('muscle_group_id'))->toBeTrue();
        });

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

        it('validates muscle_group_id is integer', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => 'not-a-number',
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('muscle_group_id'))->toBeTrue();
        });

        it('validates muscle_group_id exists', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => 999,
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('muscle_group_id'))->toBeTrue();
        });

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

        it('validates is_active is boolean when provided', function () {
            $request = new ExerciseRequest();
            $request->setUserResolver(fn() => $this->user);
            $validator = Validator::make([
                'name' => 'Test Exercise',
                'muscle_group_id' => $this->muscleGroup->id,
                'is_active' => 'not-boolean',
            ], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('is_active'))->toBeTrue();
        });

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
