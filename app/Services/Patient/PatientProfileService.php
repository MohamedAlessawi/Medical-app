<?php

namespace App\Services\Patient;

use App\Repositories\Patient\PatientProfileRepository;

class PatientProfileService
{
    protected $repository;

    public function __construct(PatientProfileRepository $repository)
    {
        $this->repository = $repository;
    }

    public function getFullProfile($userId)
    {
        return [
            'personal' => [
                'contact_info' => $this->repository->getContactInfo($userId),
                'personal_details' => $this->repository->getPersonalDetails($userId),
            ],
            'appointments' => [
                'upcoming' => $this->repository->getUpcomingAppointments($userId),
                'old' => $this->repository->getOldAppointments($userId),
            ],
            'medical_reports' => $this->repository->getMedicalReports($userId),
        ];
    }

    public function updateProfile($userId, array $data)
    {
        $this->repository->updateContactInfo($userId, $data);
        $this->repository->updatePersonalDetails($userId, $data);

        return $this->getFullProfile($userId);
    }
}
