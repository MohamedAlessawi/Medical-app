<?php

namespace App\Services\Secretary;

use App\Models\Appointment;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;

class AppointmentService
{
    use ApiResponseTrait;

    public function getDoctorAppointments($doctorId)
    {
        $today = Carbon::today();
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>=', $today)
            ->orderBy('appointment_date')
            ->with(['user:id,full_name,email,phone'])
            ->get();

        return $this->unifiedResponse(true, 'Current appointments fetched successfully.', $appointments);
    }

    public function createAppointment($data)
    {
        // تحقق من عدم وجود تعارض في الموعد
        $exists = Appointment::where('doctor_id', $data['doctor_id'])
            ->where('appointment_date', $data['appointment_date'])
            ->exists();
        if ($exists) {
            return $this->unifiedResponse(false, 'Appointment already exists for this doctor at this time.', [], [], 409);
        }
        $appointment = Appointment::create([
            'doctor_id' => $data['doctor_id'],
            'appointment_date' => $data['appointment_date'],
            'booked_by' => $data['booked_by'],
            'status' => $data['status'] ?? 'pending',
            'attendance_status' => $data['attendance_status'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
        return $this->unifiedResponse(true, 'Appointment created successfully.', $appointment);
    }

    public function updateAppointment($id, $data)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->unifiedResponse(false, 'Appointment not found.', [], [], 404);
        }
        // تحقق من التعارض إذا تم تغيير الطبيب أو الوقت
        if ((isset($data['doctor_id']) && $data['doctor_id'] != $appointment->doctor_id) || (isset($data['appointment_date']) && $data['appointment_date'] != $appointment->appointment_date)) {
            $exists = Appointment::where('doctor_id', $data['doctor_id'] ?? $appointment->doctor_id)
                ->where('appointment_date', $data['appointment_date'] ?? $appointment->appointment_date)
                ->where('id', '!=', $id)
                ->exists();
            if ($exists) {
                return $this->unifiedResponse(false, 'Another appointment exists for this doctor at this time.', [], [], 409);
            }
        }
        $appointment->update($data);
        return $this->unifiedResponse(true, 'Appointment updated successfully.', $appointment);
    }

    public function deleteAppointment($id)
    {
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->unifiedResponse(false, 'Appointment not found.', [], [], 404);
        }
        $appointment->delete();
        return $this->unifiedResponse(true, 'Appointment deleted successfully.');
    }

    public function confirmAttendance($id, $status)
    {
        $allowed = ['present', 'absent'];
        if (!in_array($status, $allowed)) {
            return $this->unifiedResponse(false, 'Invalid attendance status. Allowed values: present, absent.', [], [], 422);
        }
        $appointment = Appointment::find($id);
        if (!$appointment) {
            return $this->unifiedResponse(false, 'Appointment not found.', [], [], 404);
        }
        $appointment->attendance_status = $status;
        $appointment->save();
        return $this->unifiedResponse(true, 'Attendance status updated successfully.', $appointment);
    }
}
 