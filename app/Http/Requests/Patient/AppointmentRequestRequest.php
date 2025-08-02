<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;

class AppointmentRequestRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'doctor_id' => 'required|exists:doctors,id',
            'center_id' => 'required|exists:centers,id',
            'requested_date' => 'required|date|after:today',
            'requested_time' => 'required|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages()
    {
        return [
            'doctor_id.required' => 'Doctor is required.',
            'doctor_id.exists' => 'Selected doctor does not exist.',
            'center_id.required' => 'Center is required.',
            'center_id.exists' => 'Selected center does not exist.',
            'requested_date.required' => 'Appointment date is required.',
            'requested_date.after' => 'Appointment date must be in the future.',
            'requested_time.required' => 'Appointment time is required.',
            'requested_time.date_format' => 'Invalid time format. Use HH:MM format.',
            'notes.max' => 'Notes cannot exceed 500 characters.',
        ];
    }
}
