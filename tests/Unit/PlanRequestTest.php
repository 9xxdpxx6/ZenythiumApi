<?php

declare(strict_types=1);

use App\Http\Requests\PlanRequest;
use App\Models\Cycle;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
    $this->plan = Plan::factory()->create(['cycle_id' => $this->cycle->id]);
});

test('plan request validation passes with valid data', function () {
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

test('plan request validation fails without cycle_id', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'name' => 'Test Plan',
        'order' => 1,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('cycle_id'))->toBeTrue();
});

test('plan request validation fails without name', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => $this->cycle->id,
        'order' => 1,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

test('plan request validation fails with invalid cycle_id', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => 999999,
        'name' => 'Test Plan',
        'order' => 1,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('cycle_id'))->toBeTrue();
});

test('plan request validation fails with duplicate name in same cycle', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => $this->cycle->id,
        'name' => $this->plan->name,
        'order' => 1,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('name'))->toBeTrue();
});

test('plan request validation passes with duplicate name in different cycle', function () {
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

test('plan request validation fails with negative order', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
        'order' => 0,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('order'))->toBeTrue();
});

test('plan request validation passes without order', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('plan request validation passes with string cycle_id', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => (string) $this->cycle->id,
        'name' => 'Test Plan',
        'order' => 1,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('plan request validation fails with non-integer cycle_id', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => 'not-a-number',
        'name' => 'Test Plan',
        'order' => 1,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('cycle_id'))->toBeTrue();
});

test('plan request validation fails with too long name', function () {
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

test('plan request validation passes with maximum length name', function () {
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

test('plan request validation fails with non-integer order', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
        'order' => 'not-a-number',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->fails())->toBeTrue();
    expect($validator->errors()->has('order'))->toBeTrue();
});

test('plan request validation passes with string order', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    
    $data = [
        'cycle_id' => $this->cycle->id,
        'name' => 'Test Plan',
        'order' => '1',
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('plan request update validation allows same name for same plan', function () {
    $request = new PlanRequest();
    $request->setUserResolver(fn() => $this->user);
    $request->setRouteResolver(fn() => new \Illuminate\Routing\Route('PUT', '/plans/{plan}', []));
    $request->setRouteResolver(fn() => (object) ['plan' => $this->plan]);
    
    $data = [
        'cycle_id' => $this->cycle->id,
        'name' => $this->plan->name,
        'order' => 1,
    ];
    
    $validator = Validator::make($data, $request->rules());
    
    expect($validator->passes())->toBeTrue();
});

test('plan request custom messages are correct', function () {
    $request = new PlanRequest();
    $messages = $request->messages();
    
    expect($messages)->toBeArray();
    expect($messages['cycle_id.required'])->toBe('Цикл обязателен.');
    expect($messages['cycle_id.integer'])->toBe('Цикл должен быть числом.');
    expect($messages['cycle_id.exists'])->toBe('Выбранный цикл не существует.');
    expect($messages['name.required'])->toBe('Название плана обязательно.');
    expect($messages['name.string'])->toBe('Название плана должно быть строкой.');
    expect($messages['name.max'])->toBe('Название плана не может быть длиннее 255 символов.');
    expect($messages['name.unique'])->toBe('План с таким названием уже существует.');
    expect($messages['order.integer'])->toBe('Порядок должен быть числом.');
    expect($messages['order.min'])->toBe('Порядок должен быть больше 0.');
});

test('plan request authorize returns true', function () {
    $request = new PlanRequest();
    
    expect($request->authorize())->toBeTrue();
});
