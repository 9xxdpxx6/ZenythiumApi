<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\GoalStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Форма запроса для обновления цели
 */
final class UpdateGoalRequest extends FormRequest
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
        return [
            'title' => [
                'nullable',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'target_value' => [
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'end_date' => [
                'nullable',
                'date',
            ],
            'status' => [
                'nullable',
                'string',
                Rule::enum(GoalStatus::class),
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
            'title.string' => 'Название должно быть строкой.',
            'title.max' => 'Название не должно превышать 255 символов.',
            'target_value.numeric' => 'Целевое значение должно быть числом.',
            'target_value.min' => 'Целевое значение должно быть больше 0.',
            'end_date.date' => 'Дата окончания должна быть корректной датой.',
            'status.enum' => 'Недопустимый статус цели.',
        ];
    }
}
