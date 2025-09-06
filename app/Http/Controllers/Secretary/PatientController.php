<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\CreatePatientRequest;
use App\Http\Requests\Secretary\UpdatePatientRequest;
use App\Http\Requests\Secretary\UpdatePatientProfileRequest;
use App\Services\Secretary\PatientService;
use App\Services\Secretary\MedicalFileService;
use Illuminate\Http\Request;
use App\Services\Secretary\AppointmentService;


class PatientController extends Controller
{
    protected $patientService;
    protected $medicalFileService;


    public function __construct(PatientService $patientService, MedicalFileService $medicalFileService, AppointmentService $service)
    {
        $this->patientService = $patientService;
        $this->medicalFileService = $medicalFileService;
        $this->service = $service;
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
        return $this->patientService->updatePatientUnified($id, $request->all());
    }

    public function search(Request $request)
    {
        return $this->patientService->searchPatients($request->get('query'));
    }

    public function uploadMedicalFile(Request $request, $id)
    {
        return $this->medicalFileService->uploadMedicalFile($request, $id);
    }

    public function destroy($id)
    {
        return $this->patientService->deletePatientFromCenter($id);
    }

    public function listMedicalFiles($id)
    {
        return $this->medicalFileService->listPatientFiles($id);
    }

    public function deleteMedicalFile($patientId, $fileId)
    {
        return $this->medicalFileService->deleteMedicalFile($patientId, $fileId);
    }

    public function patientPast($patientId)
    {
        return $this->service->getPatientPastAppointments((int) $patientId);
    }

}
