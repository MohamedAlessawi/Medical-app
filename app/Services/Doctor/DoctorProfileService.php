<?php

namespace App\Services\Doctor;

use App\Repositories\Doctor\DoctorProfileRepository;

class DoctorProfileService
{
    protected $repository;

    public function __construct(DoctorProfileRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getFullProfile($userId)
    {
        $doctor = $this->repository->getDoctorData($userId);

        return [
            'basic_info' => [
                'full_name' => $doctor->user->full_name,
                'email' => $doctor->user->email,
                'phone' => $doctor->user->phone,
                'profile_photo' => $doctor->user->profile_photo,
                'specialty' => $doctor->specialty->name ?? null,
            ],
            'working_hours' => $this->repository->getWorkingHours($doctor->id),
            'statistics' => $this->repository->getAppointmentStats($doctor->id),
            'ratings' => $this->repository->getRatingStats($doctor->id),
        ];
    }
    public function updateProfile($userId, array $data)
{
    $this->repository->updateDoctorInfo($userId, $data);
    return $this->getFullProfile($userId);
}

}
