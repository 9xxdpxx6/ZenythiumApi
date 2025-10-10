<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Форма запроса для тренировок
 * 
 * Содержит правила валидации для создания и обновления тренировок.
 * Проверяет корректность данных плана, времени начала и окончания тренировки.
 */
final class WorkoutRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для выполнения запроса
     * 
     * @return bool True - пользователь авторизован
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Получить правила валидации для запроса
     * 
     * @return array Массив правил валидации:
     * - plan_id: обязательное целое число, должно существовать в таблице plans
     * - started_at: обязательная дата, не может быть в будущем
     * - finished_at: опциональная дата, должна быть после started_at
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                'exists:plans,id'
            ],
            'started_at' => [
                'required',
                'date',
                'before_or_equal:now'
            ],
            'finished_at' => [
                'nullable',
                'date',
                'after_or_equal:started_at'
            ],
        ];
    }

    /**
     * Получить пользовательские сообщения для ошибок валидации
     * 
     * @return array Массив сообщений об ошибках на русском языке
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'План обязателен.',
            'plan_id.integer' => 'План должен быть числом.',
            'plan_id.exists' => 'Выбранный план не существует.',
            'started_at.required' => 'Время начала обязательно.',
            'started_at.date' => 'Время начала должно быть корректной датой.',
            'started_at.before_or_equal' => 'Время начала не может быть в будущем.',
            'finished_at.date' => 'Время окончания должно быть корректной датой.',
            'finished_at.after_or_equal' => 'Время окончания должно быть позже или равно времени начала.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->user()->id,
        ]);
    }
}
