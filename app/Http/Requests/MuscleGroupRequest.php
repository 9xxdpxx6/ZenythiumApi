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
            'name.required' => 'The muscle group name is required.',
            'name.string' => 'The muscle group name must be a string.',
            'name.max' => 'The muscle group name may not be greater than 255 characters.',
            'name.unique' => 'The muscle group name has already been taken.',
        ];
    }
}
