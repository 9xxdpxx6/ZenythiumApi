<?php

declare(strict_types=1);

use App\Http\Requests\MuscleGroupRequest;
use App\Models\MuscleGroup;
use Illuminate\Support\Facades\Validator;

dataset('invalid_name_values', [
    'non_string' => [123],
    'too_long' => [str_repeat('a', 256)],
]);

dataset('valid_name_values', [
    'short' => ['Chest'],
    'medium' => ['Upper Body'],
    'long' => [str_repeat('a', 255)],
]);

describe('MuscleGroupRequest', function () {
    describe('validation rules', function () {
        it('validates required name field', function () {
            $request = new MuscleGroupRequest();
            $validator = Validator::make([], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('validates name with invalid values', function ($name) {
            $request = new MuscleGroupRequest();
            $validator = Validator::make(['name' => $name], $request->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        })->with('invalid_name_values');

        it('validates name with valid values', function ($name) {
            $request = new MuscleGroupRequest();
            $validator = Validator::make(['name' => $name], $request->rules());

            expect($validator->fails())->toBeFalse();
        })->with('valid_name_values');

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

            expect($messages['name.required'])->not->toBeEmpty();
            expect($messages['name.string'])->not->toBeEmpty();
            expect($messages['name.max'])->not->toBeEmpty();
            expect($messages['name.unique'])->not->toBeEmpty();
        });
    });

    describe('authorization', function () {
        it('allows all requests', function () {
            $request = new MuscleGroupRequest();

            expect($request->authorize())->toBeTrue();
        });
    });
});
