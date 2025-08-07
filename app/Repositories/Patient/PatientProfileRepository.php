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
            'address' => $user->address,
        ];
    }

    public function getPersonalDetails($userId)
    {
        $user = User::findOrFail($userId);

        return [
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
            'age' => $user->birthdate ? Carbon::parse($user->birthdate)->age : null,
            //'blood_type' => $user->blood_type ?? null, // إذا أضفتِها لاحقاً
        ];
    }

    public function updateContactInfo($userId, array $data)
    {
        $user = User::findOrFail($userId);
        $user->update([
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
        ]);
        return $user;
    }

    public function updatePersonalDetails($userId, array $data)
    {
        $user = User::findOrFail($userId);
        $user->update([
            'gender' => $data['gender'] ?? $user->gender,
            'birthdate' => $data['birthdate'] ?? $user->birthdate,
        ]);
        return $user;
    }

    public function getUpcomingAppointments($userId)
    {
        return Appointment::where('booked_by', $userId)
            ->where('appointment_date', '>=', now())
            ->with('doctor')
            ->orderBy('appointment_date')
            ->get();
    }

    public function getOldAppointments($userId)
    {
        return Appointment::where('booked_by', $userId)
            ->where('appointment_date', '<', now())
            ->with('doctor')
            ->orderByDesc('appointment_date')
            ->get();
    }

    public function getMedicalReports($userId)
    {
        return MedicalFile::where('user_id', $userId)
            ->orderByDesc('upload_date')
            ->get();
    }
}
