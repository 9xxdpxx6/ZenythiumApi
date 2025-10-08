<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class PlanRequest extends FormRequest
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
        $planId = $this->route('plan')?->id;
        $cycleId = $this->input('cycle_id');

        return [
            'cycle_id' => [
                'required',
                'integer',
                'exists:cycles,id'
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:plans,name,' . $planId . ',id,cycle_id,' . $cycleId
            ],
            'order' => [
                'nullable',
                'integer',
                'min:1'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cycle_id.required' => 'Цикл обязателен.',
            'cycle_id.integer' => 'Цикл должен быть числом.',
            'cycle_id.exists' => 'Выбранный цикл не существует.',
            'name.required' => 'Название плана обязательно.',
            'name.string' => 'Название плана должно быть строкой.',
            'name.max' => 'Название плана не может быть длиннее 255 символов.',
            'name.unique' => 'План с таким названием уже существует в этом цикле.',
            'order.integer' => 'Порядок должен быть числом.',
            'order.min' => 'Порядок должен быть больше 0.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // No need to merge user_id as Plan doesn't have direct user relationship
        // User access is controlled through Cycle relationship
    }
}
