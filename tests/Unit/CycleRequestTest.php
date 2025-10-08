<?php

declare(strict_types=1);

use App\Http\Requests\CycleRequest;
use App\Models\Cycle;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->cycle = Cycle::factory()->create(['user_id' => $this->user->id]);
});

describe('CycleRequest', function () {
    describe('validation rules', function () {
        it('passes validation with valid data', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation without name', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('fails validation without start_date', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('start_date'))->toBeTrue();
        });

        it('fails validation without end_date', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('end_date'))->toBeTrue();
        });

        it('fails validation without weeks', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weeks'))->toBeTrue();
        });
    });

    describe('name validation', function () {
        it('fails validation with too long name', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => str_repeat('a', 256),
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('passes validation with maximum length name', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => str_repeat('a', 255),
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation with duplicate name for same user', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => $this->cycle->name,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('name'))->toBeTrue();
        });

        it('passes validation with duplicate name for different user', function () {
            $otherUser = User::factory()->create();
            
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $otherUser);
            
            $data = [
                'name' => $this->cycle->name,
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('date validation', function () {
        it('fails validation with invalid start_date format', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => 'invalid-date',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('start_date'))->toBeTrue();
        });

        it('fails validation with invalid end_date format', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => 'invalid-date',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('end_date'))->toBeTrue();
        });

        it('fails validation when start_date is after end_date', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-31',
                'end_date' => '2024-01-01',
                'weeks' => 4,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('start_date'))->toBeTrue();
        });

        it('passes validation when start_date equals end_date', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-01',
                'weeks' => 1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('weeks validation', function () {
        it('fails validation with zero weeks', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 0,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weeks'))->toBeTrue();
        });

        it('fails validation with negative weeks', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => -1,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weeks'))->toBeTrue();
        });

        it('fails validation with more than 52 weeks', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 53,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weeks'))->toBeTrue();
        });

        it('passes validation with maximum weeks (52)', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 52,
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('fails validation with non-integer weeks', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 'not-a-number',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->fails())->toBeTrue();
            expect($validator->errors()->has('weeks'))->toBeTrue();
        });

        it('passes validation with string weeks that can be cast to integer', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Test Cycle',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => '4',
            ];
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });
    });

    describe('update validation', function () {
        it('validates update rules correctly', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'name' => 'Updated Cycle Name',
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
            ];
            
            // Устанавливаем данные в request
            $request->merge($data);
            
            $validator = Validator::make($data, $request->rules());
            
            expect($validator->passes())->toBeTrue();
        });

        it('validates required fields on update', function () {
            $request = new CycleRequest();
            $request->setUserResolver(fn() => $this->user);
            
            $data = [
                'start_date' => '2024-01-01',
                'end_date' => '2024-01-31',
                'weeks' => 4,
                // name отсутствует
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
            $request = new CycleRequest();
            $messages = $request->messages();
            
            expect($messages)->toBeArray();
            expect($messages['name.required'])->toBe('Название цикла обязательно.');
            expect($messages['name.string'])->toBe('Название цикла должно быть строкой.');
            expect($messages['name.max'])->toBe('Название цикла не может быть длиннее 255 символов.');
            expect($messages['name.unique'])->toBe('Цикл с таким названием уже существует.');
            expect($messages['start_date.required'])->toBe('Дата начала обязательна.');
            expect($messages['start_date.date'])->toBe('Дата начала должна быть корректной датой.');
            expect($messages['start_date.before_or_equal'])->toBe('Дата начала должна быть раньше или равна дате окончания.');
            expect($messages['end_date.required'])->toBe('Дата окончания обязательна.');
            expect($messages['end_date.date'])->toBe('Дата окончания должна быть корректной датой.');
            expect($messages['end_date.after_or_equal'])->toBe('Дата окончания должна быть позже или равна дате начала.');
            expect($messages['weeks.required'])->toBe('Количество недель обязательно.');
            expect($messages['weeks.integer'])->toBe('Количество недель должно быть числом.');
            expect($messages['weeks.min'])->toBe('Количество недель должно быть больше 0.');
            expect($messages['weeks.max'])->toBe('Количество недель не может быть больше 52.');
        });
    });

    describe('authorization', function () {
        it('authorizes all requests', function () {
            $request = new CycleRequest();
            
            expect($request->authorize())->toBeTrue();
        });
    });
});
