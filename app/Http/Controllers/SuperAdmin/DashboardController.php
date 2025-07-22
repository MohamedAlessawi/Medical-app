<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Doctor;
use App\Models\User;
use App\Models\Appointment;
use App\Traits\ApiResponseTrait;

class DashboardController extends Controller
{
    use ApiResponseTrait;

    public function getStats()
    {
        $stats = [
            'centers' => Center::count(),
            'doctors' => Doctor::count(),
            'patients' => User::whereHas('roles', fn($q) => $q->where('name', 'patient'))->count(),
            'appointments' => Appointment::count(),
        ];

        return $this->unifiedResponse(true, 'System statistics retrieved.', $stats);
    }
}
