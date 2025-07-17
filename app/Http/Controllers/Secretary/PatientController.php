<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\CreatePatientRequest;
use App\Http\Requests\Secretary\UpdatePatientRequest;
use App\Http\Requests\Secretary\UpdatePatientProfileRequest;
use App\Services\Secretary\PatientService;

class PatientController extends Controller
{
    protected $patientService;

    public function __construct(PatientService $patientService)
    {
        $this->patientService = $patientService;
    }

    public function store(CreatePatientRequest $request)
    {
        return $this->patientService->createPatientFromSecretary($request->validated());
    }

    public function index()
    {
        return $this->patientService->getAllPatientsForSecretary();
    }

    public function show($id)
    {
        return $this->patientService->getPatientDetails($id);
    }

    public function update($id, UpdatePatientRequest $request)
    {
        return $this->patientService->updatePatientUnified($id, $request->validated());
    }

    public function updateProfile($id, UpdatePatientProfileRequest $request)
    {
        return $this->patientService->updatePatientProfile($id, $request->validated());
    }

    public function search(Request $request)
    {
        return $this->patientService->searchPatients($request->query('query'));
    }
}
