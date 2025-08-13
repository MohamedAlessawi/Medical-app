<?php

namespace App\Repositories\Admin;

use App\Models\Doctor;

class DoctorRepository
{
    public function listByCenter(int $centerId)
    {
        return Doctor::with(['user','center'])
            ->where('center_id', $centerId)
            ->get();
    }

    public function linkDoctorToCenter(int $doctorUserId, int $centerId): Doctor
    {
        $existing = Doctor::where('user_id', $doctorUserId)
                          ->where('center_id', $centerId)
                          ->first();
        if ($existing) {
            $existing->is_active = true;
            $existing->save();
            return $existing;
        }
        return Doctor::create([
            'user_id'   => $doctorUserId,
            'center_id' => $centerId,
            'is_active' => true
        ]);
    }

    public function toggleStatus(int $doctorId, bool $isActive): Doctor
    {
        $doc = Doctor::findOrFail($doctorId);
        $doc->is_active = $isActive;
        $doc->save();
        return $doc;
    }

    public function removeFromCenter(int $doctorId): void
    {
        Doctor::where('id', $doctorId)->delete();
    }
}
