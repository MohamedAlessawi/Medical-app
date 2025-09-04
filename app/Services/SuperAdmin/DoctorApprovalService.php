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

    $filtered = $doctors->map(function ($doctor) {

        $certificateUrl = $doctor->certificate
                ? asset('storage/' . ltrim($doctor->certificate, '/'))
                : null;

        return [
            'doctor_profile' => [
                'id' => $doctor->id,
                'specialty_id' => $doctor->specialty_id,
                'certificate' => $certificateUrl,
                'status' => $doctor->status,
            ],
            'user' => [
                'full_name' => $doctor->user->full_name,
            ]
        ];
    });

    return $this->unifiedResponse(true, 'Pending doctors fetched', $filtered);
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
