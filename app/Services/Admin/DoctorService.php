<?php

namespace App\Services\Admin;

use App\Repositories\Admin\DoctorRepository;
use App\Repositories\Admin\InvitationRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorService
{
    use ApiResponseTrait;

    public function __construct(
        private DoctorRepository $doctorRepo,
        private InvitationRepository $invitationRepo
    ) {}

    private function myCenterId(): int
    {
        return (int) DB::table('admin_centers')->where('user_id', Auth::id())->value('center_id');
    }

    public function list()
    {
        $data = $this->doctorRepo->listByCenter($this->myCenterId());
        return $this->unifiedResponse(true, 'Doctors fetched.', $data);
    }

    public function invite(array $payload)
    {
        $inv = $this->invitationRepo->create([
            'center_id'      => $this->myCenterId(),
            'doctor_user_id' => $payload['doctor_user_id'],
            'invited_by'     => Auth::id(),
            'message'        => $payload['message'] ?? null,
        ]);
        return $this->unifiedResponse(true, 'Invitation sent.', $inv);
    }

    public function toggleStatus(int $doctorId, bool $isActive)
    {
        $doc = $this->doctorRepo->toggleStatus($doctorId, $isActive);
        return $this->unifiedResponse(true, 'Doctor status updated.', $doc);
    }

    public function remove(int $doctorId)
    {
        $this->doctorRepo->removeFromCenter($doctorId);
        return $this->unifiedResponse(true, 'Doctor unlinked from center.');
    }
}
