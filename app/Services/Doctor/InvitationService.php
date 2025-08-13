<?php

namespace App\Services\Doctor;

use App\Repositories\Admin\DoctorRepository;
use App\Repositories\Admin\InvitationRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class InvitationService
{
    use ApiResponseTrait;

    public function __construct(
        private DoctorRepository $doctorRepo,
        private InvitationRepository $invRepo
    ) {}

    public function index()
    {
        $list = $this->invRepo->findPendingForDoctor(Auth::id());
        return $this->unifiedResponse(true, 'Invitations fetched.', $list);
    }

    public function accept(int $id)
    {
        $inv = $this->invRepo->findMyPendingById($id, Auth::id());
        $this->doctorRepo->linkDoctorToCenter(Auth::id(), $inv->center_id);
        $inv->status = 'accepted';
        $inv->save();

        return $this->unifiedResponse(true, 'Invitation accepted.');
    }

    public function reject(int $id)
    {
        $inv = $this->invRepo->findMyPendingById($id, Auth::id());
        $inv->status = 'rejected';
        $inv->save();

        return $this->unifiedResponse(true, 'Invitation rejected.');
    }
}
