<?php

declare(strict_types=1);

use App\Http\Requests\ImportSharedCycleRequest;
use Illuminate\Support\Facades\Validator;

describe('ImportSharedCycleRequest', function () {
    describe('rules', function () {
        it('validates shareId as required', function () {
            $request = new ImportSharedCycleRequest();
            $rules = $request->rules();

            expect($rules)->toHaveKey('shareId');
            expect($rules['shareId'])->toContain('required');
        });

        it('validates shareId as uuid', function () {
            $request = new ImportSharedCycleRequest();
            $rules = $request->rules();

            expect($rules['shareId'])->toContain('uuid');
        });
    });

    describe('messages', function () {
        it('provides custom validation messages', function () {
            $request = new ImportSharedCycleRequest();
            $messages = $request->messages();

            expect($messages)->toHaveKey('shareId.required');
            expect($messages)->toHaveKey('shareId.uuid');
            expect($messages['shareId.required'])->toContain('обязателен');
            expect($messages['shareId.uuid'])->toContain('UUID');
        });
    });

    describe('validation', function () {
        it('passes with valid UUID', function () {
            $request = new ImportSharedCycleRequest();
            $request->merge(['shareId' => '550e8400-e29b-41d4-a716-446655440000']);

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails with invalid UUID', function () {
            $request = new ImportSharedCycleRequest();
            $request->merge(['shareId' => 'invalid-uuid']);

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            
            expect($validator->fails())->toBeTrue();
        });

        it('fails when shareId is missing', function () {
            $request = new ImportSharedCycleRequest();

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            
            expect($validator->fails())->toBeTrue();
        });
    });
});

