<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CycleRequest extends FormRequest
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
        $cycleId = $this->route('id');
        $userId = $this->user()->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:cycles,name,' . $cycleId . ',id,user_id,' . $userId
            ],
            'start_date' => [
                'nullable',
                'date',
                'required_with:end_date',
                'before_or_equal:end_date'
            ],
            'end_date' => [
                'nullable',
                'date',
                'required_with:start_date',
                'after_or_equal:start_date'
            ],
            'weeks' => [
                'required',
                'integer',
                'min:1',
                'max:52'
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название цикла обязательно.',
            'name.string' => 'Название цикла должно быть строкой.',
            'name.max' => 'Название цикла не может быть длиннее 255 символов.',
            'name.unique' => 'Цикл с таким названием уже существует.',
            'start_date.date' => 'Дата начала должна быть корректной датой.',
            'start_date.before_or_equal' => 'Дата начала должна быть раньше или равна дате окончания.',
            'end_date.date' => 'Дата окончания должна быть корректной датой.',
            'end_date.after_or_equal' => 'Дата окончания должна быть позже или равна дате начала.',
            'weeks.required' => 'Количество недель обязательно.',
            'weeks.integer' => 'Количество недель должно быть числом.',
            'weeks.min' => 'Количество недель должно быть больше 0.',
            'weeks.max' => 'Количество недель не может быть больше 52.',
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
