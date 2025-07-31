<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePatientRequest extends FormRequest
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
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // 'full_name' => 'sometimes|required|string|max:255',
            // 'phone' => 'sometimes|required|string',
            // 'gender' => 'sometimes|required|in:male,female',
            // 'birthdate' => 'sometimes|required|date',
            // 'address' => 'nullable|string',
            'condition' => 'nullable|string',
            'last_visit' => 'nullable|date',
            'status' => 'nullable|string',
        ];
    }
}
