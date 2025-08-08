<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorPatientProfileService;

class DoctorPatientProfileController extends Controller
{
    protected $service;

    public function __construct(DoctorPatientProfileService $service)
    {
        $this->service = $service;
    }

    public function show($patientId)
    {
        return $this->service->getPatientProfile($patientId);
    }
}

