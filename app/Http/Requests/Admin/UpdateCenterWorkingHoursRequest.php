<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCenterWorkingHoursRequest extends FormRequest
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
            'items' => ['required','array','size:7'],
            'items.*.day_of_week' => ['required','in:Saturday,Sunday,Monday,Tuesday,Wednesday,Thursday,Friday'],
            'items.*.is_open'     => ['required','boolean'],
            'items.*.open_time'   => ['nullable','date_format:H:i'],
            'items.*.close_time'  => ['nullable','date_format:H:i'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function($v){
            $items = $this->input('items', []);
            foreach ($items as $row) {
                if (!empty($row['is_open'])) {
                    if (empty($row['open_time']) || empty($row['close_time'])) {
                        $v->errors()->add('items', 'open_time/close_time required when is_open=true');
                        break;
                    }
                    if ($row['open_time'] >= $row['close_time']) {
                        $v->errors()->add('items', 'open_time must be before close_time for '.$row['day_of_week']);
                        break;
                    }
                }
            }
        });
    }
}
