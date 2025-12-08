<?php

declare(strict_types=1);

use App\Http\Requests\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('UpdateProfileRequest', function () {
    describe('validation rules', function () {
        it('passes validation with valid data', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'New Nickname',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation without name field', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('fails validation with empty name', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => '',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('fails validation with null name', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => null,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });
    });

    describe('name validation', function () {
        it('fails validation with too long name', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => str_repeat('a', 256), // 256 символов - больше лимита
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('passes validation with name of 255 characters', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => str_repeat('a', 255), // Ровно 255 символов
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('passes validation with name of 1 character', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'A',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation when name is not a string', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 12345,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('fails validation when name is an array', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => ['not', 'a', 'string'],
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });
    });

    describe('custom messages', function () {
        it('returns correct validation messages', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [];
            
            $validator = Validator::make($data, $request->rules(), $request->messages());
            
            expect($validator->fails())->toBeTrue();
            
            $errors = $validator->errors();
            expect($errors->first('name'))->toBe('Имя пользователя обязательно.');
        });

        it('returns correct message for too long name', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => str_repeat('a', 256),
            ];
            
            $validator = Validator::make($data, $request->rules(), $request->messages());
            
            expect($validator->fails())->toBeTrue();
            
            $errors = $validator->errors();
            expect($errors->first('name'))->toBe('Имя пользователя не может быть длиннее 255 символов.');
        });

        it('returns correct message for non-string name', function () {
            $request = new UpdateProfileRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 123,
            ];
            
            $validator = Validator::make($data, $request->rules(), $request->messages());
            
            expect($validator->fails())->toBeTrue();
            
            $errors = $validator->errors();
            expect($errors->first('name'))->toBe('Имя пользователя должно быть строкой.');
        });
    });

    describe('authorization', function () {
        it('allows all users to update profile', function () {
            $request = new UpdateProfileRequest();
            
            expect($request->authorize())->toBeTrue();
        });
    });
});






