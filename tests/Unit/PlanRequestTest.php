<?php

declare(strict_types=1);

use App\Http\Requests\PlanRequest;
use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

dataset('required_fields', [
    'name' => ['name'],
]);

dataset('invalid_cycle_ids', [
    'non_existent' => [PHP_INT_MAX],
    'non_integer' => ['not-a-number'],
]);

dataset('valid_cycle_ids', [
    'integer' => [1],
    'string_number' => ['1'],
]);

dataset('invalid_orders', [
    'negative' => [-1],
    'non_integer' => ['not-a-number'],
]);

dataset('valid_orders', [
    'positive' => [1],
    'string_number' => ['1'],
]);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
});

describe('PlanRequest', function () {
    describe('validation rules', function () {
        it('passes validation with valid data', function () {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
                'order' => 1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation without required field', function ($field) {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
                'order' => 1,
            ];
            
            unset($data[$field]);
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has($field))->toBeTrue();
        })->with('required_fields');

        it('fails validation with invalid cycle_id', function ($cycleId) {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $cycleId,
                'name' => 'Test Plan',
                'order' => 1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('cycle_id'))->toBeTrue();
        })->with('invalid_cycle_ids');

        it('passes validation with valid cycle_id', function ($cycleId) {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $cycleId,
                'name' => 'Test Plan',
                'order' => 1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        })->with('valid_cycle_ids');
    });

    describe('name validation', function () {
        it('fails validation with too long name', function () {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => str_repeat('a', 256),
                'order' => 1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('passes validation with maximum length name', function () {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => str_repeat('a', 255),
                'order' => 1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation with duplicate name in same cycle', function () {
            // Создаем новый план с тем же именем в том же цикле
            $duplicatePlan = Plan::factory()->create([
                'cycle_id' => $this->cycle->id,
                'name' => 'Duplicate Test Plan'
            ]);
            
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Duplicate Test Plan', // То же имя
                'order' => 1,
            ];
            
            // Устанавливаем данные в request
            $request->merge($data);
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('passes validation with duplicate name in different cycle', function () {
            $otherCycle = Cycle::factory()->create(['user_id' => $this->user->id]);
            
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $otherCycle->id,
                'name' => $this->plan->name,
                'order' => 1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('order validation', function () {
        it('fails validation with invalid order', function ($order) {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
                'order' => $order,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('order'))->toBeTrue();
        })->with('invalid_orders');

        it('passes validation with valid order', function ($order) {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
                'order' => $order,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        })->with('valid_orders');

        it('passes validation without order', function () {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Test Plan',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('update validation', function () {
        it('validates update rules correctly', function () {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                'name' => 'Updated Plan Name',
                'order' => 1,
            ];
            
            // Устанавливаем данные в request
            $request->merge($data);
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('validates required fields on update', function () {
            $request = new PlanRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'cycle_id' => $this->cycle->id,
                // name отсутствует
                'order' => 1,
            ];
            
            // Устанавливаем данные в request
            $request->merge($data);
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });
    });

    describe('custom messages', function () {
        it('returns correct custom messages', function () {
            $request = new PlanRequest();
            $messages = $request->messages();
            
            expect($messages)->toBeArray();
            expect($messages['cycle_id.integer'])->toBe('Цикл должен быть числом.');
            expect($messages['cycle_id.exists'])->toBe('Выбранный цикл не существует.');
            expect($messages['name.required'])->toBe('Название плана обязательно.');
            expect($messages['name.string'])->toBe('Название плана должно быть строкой.');
            expect($messages['name.max'])->toBe('Название плана не может быть длиннее 255 символов.');
            expect($messages['name.unique'])->toBe('План с таким названием уже существует в этом цикле.');
            expect($messages['order.integer'])->toBe('Порядок должен быть числом.');
            expect($messages['order.min'])->toBe('Порядок должен быть больше 0.');
        });
    });

    describe('authorization', function () {
        it('authorizes all requests', function () {
            $request = new PlanRequest();
            
            expect($request->authorize())->toBeTrue();
        });
    });
});
