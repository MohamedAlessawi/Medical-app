<?php

namespace App\Repositories\Admin;

use App\Models\DoctorInvitation;

class InvitationRepository
{
    public function create(array $data): DoctorInvitation
    {
        return DoctorInvitation::create($data);
    }

    public function findPendingForDoctor(int $doctorUserId)
    {
        return DoctorInvitation::where('doctor_user_id', $doctorUserId)
                               ->where('status','pending')
                               ->get();
    }

    public function findMyPendingById(int $id, int $doctorUserId): DoctorInvitation
    {
        return DoctorInvitation::where('id',$id)
               ->where('doctor_user_id',$doctorUserId)
               ->where('status','pending')
               ->firstOrFail();
    }
}
