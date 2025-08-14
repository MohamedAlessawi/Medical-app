<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class DoctorCenterService
{
    use ApiResponseTrait;

    public function getDoctorCenters()
    {
        $user = Auth::user();

        $doctorCenters = Doctor::with('center')
            ->where('user_id', $user->id)
            ->get()
            ->map(function ($doctor) {
                return [
                    'center_id' => $doctor->center->id,
                    'center_name' => $doctor->center->name,
                    'address' => $doctor->center->address,
                    'phone' => $doctor->center->phone,
                ];
            });

        return $this->unifiedResponse(true, 'Centers fetched successfully.', $doctorCenters);
    }
}
