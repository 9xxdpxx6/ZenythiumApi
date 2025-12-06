<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GoalType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Форма запроса для создания цели
 */
final class StoreGoalRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения запроса
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить правила валидации для запроса
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $exerciseTypes = [
            GoalType::EXERCISE_MAX_WEIGHT->value,
            GoalType::EXERCISE_MAX_REPS->value,
            GoalType::EXERCISE_VOLUME->value,
        ];

        return [
            'type' => [
                'required',
                'string',
                Rule::enum(GoalType::class),
            ],
            'title' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'target_value' => [
                'required',
                'numeric',
                'min:0.01',
            ],
            'start_date' => [
                'required',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after:start_date',
            ],
            'exercise_id' => [
                Rule::requiredIf(in_array($this->input('type'), $exerciseTypes)),
                'nullable',
                'integer',
                'exists:exercises,id',
            ],
        ];
    }

    /**
     * Получить пользовательские сообщения для ошибок валидации
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'type.required' => 'Тип цели обязателен.',
            'type.enum' => 'Недопустимый тип цели.',
            'title.required' => 'Название цели обязательно.',
            'title.string' => 'Название должно быть строкой.',
            'title.max' => 'Название не должно превышать 255 символов.',
            'target_value.required' => 'Целевое значение обязательно.',
            'target_value.numeric' => 'Целевое значение должно быть числом.',
            'target_value.min' => 'Целевое значение должно быть больше 0.',
            'start_date.required' => 'Дата начала обязательна.',
            'start_date.date' => 'Дата начала должна быть корректной датой.',
            'end_date.date' => 'Дата окончания должна быть корректной датой.',
            'end_date.after' => 'Дата окончания должна быть позже даты начала.',
            'exercise_id.required' => 'Упражнение обязательно для данного типа цели.',
            'exercise_id.exists' => 'Выбранное упражнение не существует.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->user()?->id,
        ]);
    }
}
