<?php

namespace App\Repositories\Doctor;

use App\Models\DoctorProfile;

class DoctorProfileRepository
{
    public function create(array $data)
    {
        return DoctorProfile::create($data);
    }

    public function updateOrCreate(array $where, array $data)
    {
        return DoctorProfile::updateOrCreate($where, $data);
    }

    public function getByUserId($userId)
    {
        return DoctorProfile::where('user_id', $userId)->first();
    }
    public function getPendingProfiles()
    {
        return DoctorProfile::with('user')->where('status', 'pending')->get();
    }

    public function find($id)
    {
        return DoctorProfile::findOrFail($id);
    }

    public function updateStatus($id, $status)
    {
        $profile = $this->find($id);
        $profile->status = $status;
        $profile->save();
        return $profile;
    }
}

