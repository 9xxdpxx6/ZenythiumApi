<?php

declare(strict_types=1);

use App\Http\Requests\MetricRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('MetricRequest', function () {
    describe('rules', function () {
        it('validates required fields', function () {
            $request = new MetricRequest();
            $rules = $request->rules();

            expect($rules)->toHaveKey('date');
            expect($rules)->toHaveKey('weight');
            expect($rules['date'])->toContain('required');
            expect($rules['weight'])->toContain('required');
        });

        it('validates date format', function () {
            $request = new MetricRequest();
            $rules = $request->rules();

            expect($rules['date'])->toContain('date');
            expect($rules['date'])->toContain('before_or_equal:today');
        });

        it('validates weight format', function () {
            $request = new MetricRequest();
            $rules = $request->rules();

            expect($rules['weight'])->toContain('numeric');
            expect($rules['weight'])->toContain('min:0');
            expect($rules['weight'])->toContain('max:1000');
        });

        it('validates note as optional', function () {
            $request = new MetricRequest();
            $rules = $request->rules();

            expect($rules['note'])->toContain('nullable');
            expect($rules['note'])->toContain('string');
            expect($rules['note'])->toContain('max:1000');
        });
    });

    describe('messages', function () {
        it('provides custom validation messages', function () {
            $request = new MetricRequest();
            $messages = $request->messages();

            expect($messages)->toHaveKey('date.required');
            expect($messages)->toHaveKey('date.date');
            expect($messages)->toHaveKey('date.before_or_equal');
            expect($messages)->toHaveKey('weight.required');
            expect($messages)->toHaveKey('weight.numeric');
            expect($messages)->toHaveKey('weight.min');
            expect($messages)->toHaveKey('weight.max');
            expect($messages)->toHaveKey('note.string');
            expect($messages)->toHaveKey('note.max');
        });
    });

    describe('authorize', function () {
        it('allows all requests', function () {
            $request = new MetricRequest();

            expect($request->authorize())->toBeTrue();
        });
    });

    describe('validation', function () {
        it('passes with valid data', function () {
            $data = [
                'date' => '2024-03-15',
                'weight' => 75.5,
                'note' => 'Test metric',
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('fails without required fields', function () {
            $data = [];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('date'))->toBeTrue();
            expect($validator->errors()->has('weight'))->toBeTrue();
        });

        it('fails with invalid date', function () {
            $data = [
                'date' => 'invalid-date',
                'weight' => 75.5,
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('date'))->toBeTrue();
        });

        it('fails with future date', function () {
            $data = [
                'date' => '2030-01-01',
                'weight' => 75.5,
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('date'))->toBeTrue();
        });

        it('fails with invalid weight', function () {
            $data = [
                'date' => '2024-03-15',
                'weight' => 'not-a-number',
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weight'))->toBeTrue();
        });

        it('fails with negative weight', function () {
            $data = [
                'date' => '2024-03-15',
                'weight' => -10,
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weight'))->toBeTrue();
        });

        it('fails with weight exceeding maximum', function () {
            $data = [
                'date' => '2024-03-15',
                'weight' => 1001,
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weight'))->toBeTrue();
        });

        it('fails with note exceeding maximum length', function () {
            $data = [
                'date' => '2024-03-15',
                'weight' => 75.5,
                'note' => str_repeat('a', 1001),
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('note'))->toBeTrue();
        });

        it('passes with valid note', function () {
            $data = [
                'date' => '2024-03-15',
                'weight' => 75.5,
                'note' => 'Valid note',
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->passes())->toBeTrue();
        });

        it('passes without note', function () {
            $data = [
                'date' => '2024-03-15',
                'weight' => 75.5,
            ];

            $validator = Validator::make($data, (new MetricRequest())->rules());

            expect($validator->passes())->toBeTrue();
        });
    });
});
