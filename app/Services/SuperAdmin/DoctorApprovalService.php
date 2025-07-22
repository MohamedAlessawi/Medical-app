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
        return [
            'doctor_profile' => [
                'id' => $doctor->id,
                'about_me' => $doctor->about_me,
                'years_of_experience' => $doctor->years_of_experience,
                'specialty_id' => $doctor->specialty_id,
                'certificate' => $doctor->certificate,
                'status' => $doctor->status,
            ],
            'user' => [
                'full_name' => $doctor->user->full_name,
                'profile_photo' => $doctor->user->profile_photo,
                'birthdate' => $doctor->user->birthdate,
                'gender' => $doctor->user->gender,
                'address' => $doctor->user->address,
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
