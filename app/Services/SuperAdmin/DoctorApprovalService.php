<?php

namespace App\Services\SuperAdmin;

use App\Repositories\Doctor\DoctorProfileRepository;
use App\Traits\ApiResponseTrait;

class DoctorApprovalService
{
    use ApiResponseTrait;

    protected $doctorRepo;

    public function __construct(DoctorProfileRepository $doctorRepo)
    {
        $this->doctorRepo = $doctorRepo;
    }

    public function listPending()
    {
        $doctors = $this->doctorRepo->getPendingProfiles();
        return $this->unifiedResponse(true, 'Pending doctors fetched', $doctors);
    }

    public function approve($id)
    {
        $profile = $this->doctorRepo->updateStatus($id, 'approved');
        return $this->unifiedResponse(true, 'Doctor approved', $profile);
    }

    public function reject($id)
    {
        $profile = $this->doctorRepo->updateStatus($id, 'rejected');
        return $this->unifiedResponse(true, 'Doctor rejected', $profile);
    }
}
