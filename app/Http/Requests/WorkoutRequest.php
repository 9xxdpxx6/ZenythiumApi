<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class WorkoutRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
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
     * Get custom messages for validator errors.
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
