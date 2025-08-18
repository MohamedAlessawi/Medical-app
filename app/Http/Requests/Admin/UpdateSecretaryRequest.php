<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecretaryRequest extends FormRequest
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
        $userId = (int) $this->route('userId'); // من الراوت /admin/secretaries/{userId}

        return [
            // من جدول users
            'full_name' => ['sometimes','string','max:255'],
            'email'     => ['sometimes','email','max:255','unique:users,email,'.$userId],
            'phone'     => ['sometimes','nullable','string','max:20','unique:users,phone,'.$userId],

            // من جدول secretaries
            'shift'     => ['sometimes','in:morning,evening,night'],
            'is_active' => ['sometimes','boolean'],
        ];
    }
}
