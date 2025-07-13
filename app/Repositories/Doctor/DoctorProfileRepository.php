<?php

namespace App\Repositories\Doctor;

use App\Models\Doctor;
use App\Models\User;
use App\Models\WorkingHour;
use App\Models\Appointment;
use App\Models\Rating;
use Illuminate\Support\Facades\DB;

class DoctorProfileRepository
{
    public function getDoctorData($userId)
    {
        return Doctor::with(['specialty', 'user'])
            ->where('user_id', $userId)
            ->firstOrFail();
    }

    public function getWorkingHours($doctorId)
    {
        return WorkingHour::where('doctor_id', $doctorId)->get();
    }

    public function getAppointmentStats($doctorId)
    {
        $appointments = Appointment::where('doctor_id', $doctorId)->get();

        return [
            'total_appointments' => $appointments->count(),
            'unique_patients' => $appointments->pluck('booked_by')->unique()->count(),
        ];
    }

    public function getRatingStats($doctorId)
    {
        $ratings = Rating::whereHas('appointment', function ($q) use ($doctorId) {
            $q->where('doctor_id', $doctorId);
        })->get();

        return [
            'average_score' => round($ratings->avg('score'), 1),
            'total_ratings' => $ratings->count(),
        ];
    }
    public function updateDoctorInfo($userId, array $data)
{
    $doctor = \App\Models\Doctor::where('user_id', $userId)->firstOrFail();
    $doctor->update([
        'about_me' => $data['about_me'] ?? $doctor->about_me,
        'specialty_id' => $data['specialty_id'] ?? $doctor->specialty_id,
    ]);

    $user = $doctor->user;
    $user->update([
        'phone' => $data['phone'] ?? $user->phone,
        'profile_photo' => $data['profile_photo'] ?? $user->profile_photo,
    ]);

    return $doctor;
}

}
