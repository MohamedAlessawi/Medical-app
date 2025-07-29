<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecretaryProfileRequest extends FormRequest
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
            'full_name' => 'sometimes|string|max:255',
            'phone'     => 'sometimes|string|max:20',
            'email'     => 'sometimes|email',
            'address'   => 'nullable|string|max:255',
            'profile_photo'=> 'nullable|image|mimes:jpeg,png,jpg|max:2048',

        ];
    }
    public function all($keys = null)
    {
        return parent::all($keys);
    }

}
