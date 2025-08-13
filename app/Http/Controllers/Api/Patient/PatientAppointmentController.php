<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patient\PatientAppointmentService;
use App\Http\Requests\Patient\AppointmentRequestRequest;
use Illuminate\Http\Request;

class PatientAppointmentController extends Controller
{
    protected $appointmentService;

    public function __construct(PatientAppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }


    public function getCenters(Request $request)
    {
        return $this->appointmentService->getCenters($request);
    }


    public function getSpecialties(Request $request)
    {
        return $this->appointmentService->getSpecialties($request);
    }


    public function getDoctorsByCenterAndSpecialty(Request $request, $centerId, $specialtyId)
    {
        return $this->appointmentService->getDoctorsByCenterAndSpecialty($centerId, $specialtyId);
    }


    public function getDoctorCenters($doctorId)
    {
        return $this->appointmentService->getDoctorCenters($doctorId);
    }


    public function getAvailableSlots(Request $request, $doctorId, $centerId)
    {
        return $this->appointmentService->getAvailableSlots($doctorId, $centerId, $request);
    }


    public function requestAppointment(AppointmentRequestRequest $request)
    {
        return $this->appointmentService->requestAppointment($request);
    }


    public function getAppointmentRequests(Request $request)
    {
        return $this->appointmentService->getAppointmentRequests($request);
    }
}
