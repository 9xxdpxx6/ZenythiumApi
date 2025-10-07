<?php

declare(strict_types=1);

use App\Http\Requests\MuscleGroupRequest;
use App\Models\MuscleGroup;
use Illuminate\Support\Facades\Validator;

describe('MuscleGroupRequest', function () {
    describe('validation rules', function () {
        it('validates required name field', function () {
            $request = new MuscleGroupRequest();
            $validator = Validator::make([], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('validates name is string', function () {
            $request = new MuscleGroupRequest();
            $validator = Validator::make(['name' => 123], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('validates name max length', function () {
            $request = new MuscleGroupRequest();
            $longName = str_repeat('a', 256);
            $validator = Validator::make(['name' => $longName], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('validates unique name for creation', function () {
            MuscleGroup::factory()->create(['name' => 'Chest']);

            $request = new MuscleGroupRequest();
            $validator = Validator::make(['name' => 'Chest'], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('passes validation with valid data', function () {
            $request = new MuscleGroupRequest();
            $validator = Validator::make(['name' => 'Chest'], $request->rules());

            expect($validator->fails())->toBeFalse();
        });
    });

    describe('custom messages', function () {
        it('returns custom validation messages', function () {
            $request = new MuscleGroupRequest();
            $messages = $request->messages();

            expect($messages)->toHaveKey('name.required');
            expect($messages)->toHaveKey('name.string');
            expect($messages)->toHaveKey('name.max');
            expect($messages)->toHaveKey('name.unique');

            expect($messages['name.required'])->toBe('The muscle group name is required.');
            expect($messages['name.string'])->toBe('The muscle group name must be a string.');
            expect($messages['name.max'])->toBe('The muscle group name may not be greater than 255 characters.');
            expect($messages['name.unique'])->toBe('The muscle group name has already been taken.');
        });
    });

    describe('authorization', function () {
        it('allows all requests', function () {
            $request = new MuscleGroupRequest();

            expect($request->authorize())->toBeTrue();
        });
    });
});
