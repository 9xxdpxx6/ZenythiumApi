<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class MuscleGroupRequest extends FormRequest
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
        $muscleGroupId = $this->route('muscle_group')?->id;

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                'unique:muscle_groups,name,' . $muscleGroupId
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название группы мышц обязательно.',
            'name.string' => 'Название группы мышц должно быть строкой.',
            'name.max' => 'Название группы мышц не может быть длиннее 255 символов.',
            'name.unique' => 'Группа мышц с таким названием уже существует.',
        ];
    }
}
