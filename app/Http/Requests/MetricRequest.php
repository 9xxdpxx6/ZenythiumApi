<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MetricRequest extends FormRequest
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
            'date' => [
                'required',
                'date',
                'before_or_equal:today'
            ],
            'weight' => [
                'required',
                'numeric',
                'min:0',
                'max:1000'
            ],
            'note' => [
                'nullable',
                'string',
                'max:1000'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Дата обязательна.',
            'date.date' => 'Дата должна быть корректной датой.',
            'date.before_or_equal' => 'Дата не может быть в будущем.',
            'weight.required' => 'Вес обязателен.',
            'weight.numeric' => 'Вес должен быть числом.',
            'weight.min' => 'Вес не может быть отрицательным.',
            'weight.max' => 'Вес не может превышать 1000 кг.',
            'note.string' => 'Заметка должна быть текстом.',
            'note.max' => 'Заметка не может превышать 1000 символов.',
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
