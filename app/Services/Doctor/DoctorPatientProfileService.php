<?php

namespace App\Services\Doctor;

use App\Models\{User, Appointment, MedicalFile};
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponseTrait;
use Carbon\Carbon;

class DoctorPatientProfileService
{
    use ApiResponseTrait;

    public function getPatientProfile($patientId)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return $this->unifiedResponse(false, 'Unauthorized. You are not a doctor.', [], [], 403);
        }

        $user = User::where('id', $patientId)->whereHas('roles', function($q) {
            $q->where('name', 'patient');
        })->first();

        if (!$user) {
            return $this->unifiedResponse(false, 'Patient not found.', [], [], 404);
        }

        $age = $user->birthdate ? Carbon::parse($user->birthdate)->age : null;

        $appointments = Appointment::where('doctor_id', $doctor->id)
            ->where('booked_by', $patientId)
            ->where('status', 'confirmed')
            ->whereDate('appointment_date', '<', now())
            ->orderByDesc('appointment_date')
            ->get([
                'id', 'appointment_date', 'status', 'attendance_status', 'notes'
            ]);

        $medicalFiles = MedicalFile::where('user_id', $patientId)
        ->orderByDesc('upload_date')
        ->get([
            'id', 'file_url', 'type', 'upload_date'
        ]);

        return $this->unifiedResponse(true, 'Patient profile fetched successfully.', [
            'personal_info' => [
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'gender' => $user->gender,
                'birthdate' => $user->birthdate,
                'age' => $age,
            ],
            'past_visits' => $appointments,
            'medical_files' => $medicalFiles,
        ]);
    }
}

