<?php

namespace App\Repositories\Doctor;

use App\Models\Appointment;
use Carbon\Carbon;

class AppointmentRepository
{
    public function getConfirmedAppointmentsByDoctor($doctorId, $filter = null, $perPage = 10)
    {
        $query = Appointment::where('doctor_id', $doctorId)
            ->where('status', 'confirmed');

        if ($filter === 'today') {
            $query->whereDate('appointment_date', Carbon::today());
        } elseif ($filter === 'this_week') {
            $query->whereBetween('appointment_date', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ]);
        } elseif ($this->isValidDate($filter)) {
            $query->whereDate('appointment_date', $filter);
        }

        return $query->orderBy('appointment_date', 'asc')->paginate($perPage);
    }

    private function isValidDate($date)
    {
        return Carbon::hasFormat($date, 'Y-m-d');
    }
}
