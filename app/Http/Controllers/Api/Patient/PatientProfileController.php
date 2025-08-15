<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Patient\PatientProfileService;
use App\Http\Requests\Patient\UpdatePatientProfileRequest;

class PatientProfileController extends Controller
{
    protected $service;

    public function __construct(PatientProfileService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request)
    {
        $userId = $request->user()->id;
        return $this->service->getFullProfile($userId);
    }

    public function update(UpdatePatientProfileRequest $request)
    {
        $userId = $request->user()->id;
        return $this->service->updateProfile($userId, $request->validated());
    }

    public function updateMedical(UpdatePatientProfileRequest $request)
    {
        $userId = $request->user()->id;
        return $this->service->updateMedicalInfo($userId, $request->validated());
    }

    public function updateEmergency(UpdatePatientProfileRequest $request)
    {
        $userId = $request->user()->id;
        return $this->service->updateEmergencyContacts($userId, $request->validated());
    }

    public function updateLifestyle(UpdatePatientProfileRequest $request)
    {
        $userId = $request->user()->id;
        return $this->service->updateLifestyleInfo($userId, $request->validated());
    }

    public function updateInsurance(UpdatePatientProfileRequest $request)
    {
        $userId = $request->user()->id;
        return $this->service->updateInsuranceInfo($userId, $request->validated());
    }
}
