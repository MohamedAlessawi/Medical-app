<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class RejectAppointmentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'reason' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'reason.max' => 'Rejection reason cannot exceed 500 characters.',
        ];
    }
}
