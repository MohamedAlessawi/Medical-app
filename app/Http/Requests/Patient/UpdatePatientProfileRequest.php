<?php

namespace App\Http\Requests\Patient;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientProfileRequest extends FormRequest
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
        return [
            'condition' => 'nullable|string|max:500',
            'medical_history' => 'nullable|string|max:1000',
            'allergies' => 'nullable|string|max:500',
            'current_medications' => 'nullable|string|max:1000',
            'family_medical_history' => 'nullable|string|max:1000',

            'blood_type' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'height' => 'nullable|numeric|min:50|max:250',
            'weight' => 'nullable|numeric|min:20|max:300',

            'emergency_contact_name' => 'nullable|string|max:100',
            'emergency_contact_phone' => 'nullable|string|max:20',

            'smoking_status' => ['nullable', Rule::in(['non_smoker', 'former_smoker', 'current_smoker'])],
            'alcohol_consumption' => ['nullable', Rule::in(['none', 'occasional', 'moderate', 'heavy'])],
            'lifestyle_notes' => 'nullable|string|max:500',

            'last_visit' => 'nullable|date|before_or_equal:today',
            'next_follow_up' => 'nullable|date|after:today',
            'treatment_notes' => 'nullable|string|max:1000',
            'preferred_language' => ['nullable', Rule::in(['ar', 'en'])],

            'insurance_provider' => 'nullable|string|max:100',
            'insurance_number' => 'nullable|string|max:50',
            'insurance_expiry' => 'nullable|date|after:today',

            'status' => ['nullable', Rule::in(['active', 'inactive', 'follow-up', 'discharged'])],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'height.min' => 'الطول يجب أن يكون أكثر من 50 سم',
            'height.max' => 'الطول يجب أن يكون أقل من 250 سم',
            'weight.min' => 'الوزن يجب أن يكون أكثر من 20 كجم',
            'weight.max' => 'الوزن يجب أن يكون أقل من 300 كجم',
            'next_follow_up.after' => 'موعد المتابعة يجب أن يكون في المستقبل',
            'insurance_expiry.after' => 'تاريخ انتهاء التأمين يجب أن يكون في المستقبل',
            'last_visit.before_or_equal' => 'تاريخ آخر زيارة يجب أن يكون في الماضي أو اليوم',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'condition' => 'الحالة الصحية',
            'medical_history' => 'التاريخ الطبي',
            'allergies' => 'الحساسية',
            'current_medications' => 'الأدوية الحالية',
            'family_medical_history' => 'التاريخ الطبي العائلي',
            'blood_type' => 'فصيلة الدم',
            'height' => 'الطول',
            'weight' => 'الوزن',
            'emergency_contact_name' => 'اسم شخص الطوارئ',
            'emergency_contact_phone' => 'هاتف الطوارئ',
            'smoking_status' => 'حالة التدخين',
            'alcohol_consumption' => 'استهلاك الكحول',
            'lifestyle_notes' => 'ملاحظات نمط الحياة',
            'next_follow_up' => 'موعد المتابعة القادمة',
            'treatment_notes' => 'ملاحظات العلاج',
            'preferred_language' => 'اللغة المفضلة',
            'insurance_provider' => 'مزود التأمين',
            'insurance_number' => 'رقم التأمين',
            'insurance_expiry' => 'تاريخ انتهاء التأمين',
        ];
    }
}
