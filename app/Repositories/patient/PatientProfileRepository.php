<?php

namespace App\Repositories\Patient;

use App\Models\User;
use App\Models\Appointment;
use App\Models\MedicalFile;
use Illuminate\Support\Carbon;

class PatientProfileRepository
{
    public function getContactInfo($userId)
    {
        $user = User::findOrFail($userId);

        return [
            'email' => $user->email,
            'phone' => $user->phone,
            //'location' => $user->location ?? null,//مو موجودة
        ];
    }

    public function getPersonalDetails($userId)
    {
        $user = User::with('patientProfile')->findOrFail($userId);
        $profile = $user->patientProfile;

        return [
            'gender' => $profile?->gender,
            'age' => $profile?->birth_date ? Carbon::parse($profile->birth_date)->age : null,
            'blood_type' => $profile?->blood_type,
        ];
    }

    public function getUpcomingAppointments($userId)
    {
        return Appointment::where('booked_by', $userId)
            ->where('date', '>=', now())
            ->with('doctor.user')
            ->orderBy('date')
            ->get();
    }

    public function getOldAppointments($userId)
    {
        return Appointment::where('booked_by', $userId)
            ->where('date', '<', now())
            ->with('doctor.user')
            ->orderByDesc('date')
            ->get();
    }

    public function getMedicalReports($userId)
    {
        return MedicalFile::where('user_id', $userId)
            ->with('doctor.user')
            ->orderByDesc('created_at')
            ->get();
    }
    public function updateContactInfo($userId, array $data)
{
    $user = User::findOrFail($userId);
    $user->update([
        'phone' => $data['phone'] ?? $user->phone,
        'location' => $data['location'] ?? $user->location,
    ]);
    return $user;
}

public function updatePersonalDetails($userId, array $data)
{
    return \App\Models\PatientProfile::updateOrCreate(
        ['user_id' => $userId],
        [
            'gender' => $data['gender'] ?? null,
            'blood_type' => $data['blood_type'] ?? null,
            'birth_date' => $data['birth_date'] ?? null,
        ]
    );
}
}
