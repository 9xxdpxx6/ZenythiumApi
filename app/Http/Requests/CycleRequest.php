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
                'date'
            ],
            'end_date' => [
                'nullable',
                'date'
            ],
            'weeks' => [
                'required',
                'integer',
                'min:1',
                'max:52'
            ],
            'plan_ids' => [
                'nullable',
                'array',
                'max:20' // Ограничиваем количество планов
            ],
            'plan_ids.*' => [
                'integer',
                'exists:plans,id'
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
            'plan_ids.array' => 'Планы должны быть переданы в виде массива.',
            'plan_ids.max' => 'Нельзя привязать больше 20 планов к одному циклу.',
            'plan_ids.*.integer' => 'ID плана должен быть числом.',
            'plan_ids.*.exists' => 'Один или несколько планов не найдены.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'user_id' => $this->user()->id,
            // Явно конвертируем пустые строки в null для полей дат
            'start_date' => $this->input('start_date') === '' ? null : $this->input('start_date'),
            'end_date' => $this->input('end_date') === '' ? null : $this->input('end_date'),
        ]);
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Используем данные из Form Request, а не из валидатора
            $startDate = $this->input('start_date');
            $endDate = $this->input('end_date');
            
            // Если есть дата окончания, но нет даты начала - ошибка
            if ($endDate && !$startDate) {
                $validator->errors()->add('end_date', 'Дата окончания не может быть установлена без даты начала.');
            }
            
            // Если есть обе даты, но дата окончания раньше даты начала - ошибка
            if ($startDate && $endDate && $startDate > $endDate) {
                $validator->errors()->add('end_date', 'Дата окончания не может быть раньше даты начала.');
            }
        });
    }
}
