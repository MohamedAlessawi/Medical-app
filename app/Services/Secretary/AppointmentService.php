<?php

namespace App\Services\Secretary;

use App\Models\Appointment;
use App\Models\MedicalFile;
use App\Models\User;
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

        $exists = Appointment::where('doctor_id', $data['doctor_id'])
            ->where('appointment_date', $data['appointment_date'])
            ->where('status', '!=', 'cancelled')
            ->exists();
        if ($exists) {
            return $this->unifiedResponse(false, 'Appointment already exists for this doctor at this time.', [], [], 409);
        }
        $appointment = Appointment::create([
            // 'booked_by' => $data['booked_by'],
            'booked_by' => auth()->id(),
            'doctor_id' => $data['doctor_id'],
            'appointment_date' => $data['appointment_date'],
            'patient_id' => $data['patient_id'],
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

        if ((isset($data['doctor_id']) && $data['doctor_id'] != $appointment->doctor_id) || (isset($data['appointment_date']) && $data['appointment_date'] != $appointment->appointment_date)) {
            $exists = Appointment::where('doctor_id', $data['doctor_id'] ?? $appointment->doctor_id)
                ->where('appointment_date', $data['appointment_date'] ?? $appointment->appointment_date)
                ->where('id', '!=', $id)
                ->where('status', '!=', 'cancelled')
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

    public function getDashboardStats()
    {
        $centerId = auth()->user()->secretaries->first()->center_id;

        $pendingAppointments = Appointment::whereHas('doctor', function($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->where('status', 'pending')->count();

        $newFiles = MedicalFile::whereDate('upload_date', now()->toDateString())
            ->whereHas('user.userCenters', function($q) use ($centerId) {
                $q->where('center_id', $centerId);
            })->count();

        $todaysAppointments = Appointment::whereHas('doctor', function($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->whereDate('appointment_date', now()->toDateString())->count();

        $totalPatients = User::whereHas('userCenters', function($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->whereHas('roles', function($q) {
            $q->where('name', 'patient');
        })->count();
        return $this->unifiedResponse(true, 'Dashboard stats fetched successfully', [
            'pending_appointments' => $pendingAppointments,
            'new_files' => $newFiles,
            'todays_appointments' => $todaysAppointments,
            'total_patients' => $totalPatients,
        ]);
    }

    public function getTodaysAppointmentsForCenter()
    {
        $centerId = auth()->user()->secretaries->first()->center_id;
        $appointments = Appointment::whereHas('doctor', function($q) use ($centerId) {
                $q->where('center_id', $centerId);
            })
            ->whereDate('appointment_date', now()->toDateString())
            ->with([
                'user:id,full_name',
                'doctor.user:id,full_name',
                'doctor.doctorProfile:id,user_id,specialization,visit_type'
            ])
            ->orderBy('appointment_date')
            ->get()
            ->map(function($appointment) {
                return [
                    'status' => $appointment->status,
                    'time' => date('H:i', strtotime($appointment->appointment_date)),
                    'patient_name' => $appointment->user->full_name ?? null,
                    'doctor_name' => $appointment->doctor->user->full_name ?? null,
                    'visit_type' => $appointment->doctor->doctorProfile->visit_type ?? ($appointment->notes ?? null),
                ];
            });
        return $this->unifiedResponse(true, 'Today\'s appointments fetched successfully', $appointments);
    }

}
