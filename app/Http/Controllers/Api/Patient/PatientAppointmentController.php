<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Services\Patient\PatientAppointmentService;
use App\Http\Requests\Patient\AppointmentRequestRequest;
use Illuminate\Http\Request;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;


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


     /////////////////////////////////////////////////////////////////////////


     public function getCenterDetails($centerId, PatientAppointmentService $service)
     {
         return $service->getCenterDetails($centerId);
     }
     
     ///////////////////////////////////////////////////////////////////////////////
     
     public function getAvailableSlotsBySpecialty($centerId, $specialtyId, $doctorId, Request $request,PatientAppointmentService $service)
     {
         return $service->getAvailableSlotsBySpecialty($centerId, $specialtyId, $doctorId,$request);
     }
     
     /////////////////////////////////////////////////////////////////////////////////
     
     public function getDoctorProfile($doctorId, PatientAppointmentService $service)
     {
         return $service->getDoctorProfile($doctorId);
     }
     
     ///////////////////////////////////////////////////////////////////////////////
     
     public function getCentersAndDoctorsBySpecialty($specialtyId, PatientAppointmentService $service)
     {
         return $service->getCentersAndDoctorsBySpecialty($specialtyId);
     }

     public function cancelPendingAppointment($id)
     {
         return $this->appointmentService->cancelPendingAppointmentRequest($id);
     }



     public function updateFcmToken(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user();
        $user->fcm_token = $request->fcm_token;
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'FCM token updated successfully.',
            'fcm_token' => $user->fcm_token,
        ]);
    }

    public function getPastAppointmentsForPatient()
    {
        return $this->appointmentService->getPastAppointmentsForPatient();
    }

}
