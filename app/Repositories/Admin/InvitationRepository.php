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

    public function toggleForCenterAndDoctor(int $centerId, int $doctorUserId, int $invitedBy, ?string $message = null): array
    {
        $pending = \App\Models\DoctorInvitation::where('center_id', $centerId)
            ->where('doctor_user_id', $doctorUserId)
            ->where('status', 'pending');

        if ($pending->exists()) {
            $pending->update(['status' => 'expired']);
            return ['mode' => 'canceled', 'invitation_status' => null];
        }

        $inv = \App\Models\DoctorInvitation::create([
            'center_id'      => $centerId,
            'doctor_user_id' => $doctorUserId,
            'invited_by'     => $invitedBy,
            'message'        => $message,
            'status'         => 'pending',
        ]);

        return ['mode' => 'sent', 'invitation_status' => 'pending', 'invitation_id' => $inv->id];
    }

}
