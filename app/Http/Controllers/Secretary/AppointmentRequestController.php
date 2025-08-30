<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Services\Secretary\AppointmentRequestService;
use App\Http\Requests\Secretary\RejectAppointmentRequestRequest;
use Illuminate\Http\Request;

class AppointmentRequestController extends Controller
{
    protected $appointmentRequestService;

    public function __construct(AppointmentRequestService $appointmentRequestService)
    {
        $this->appointmentRequestService = $appointmentRequestService;
    }


    public function index(Request $request)
    {
        return $this->appointmentRequestService->getAppointmentRequests($request);
    }


    public function show($id)
    {
        return $this->appointmentRequestService->getAppointmentRequest($id);
    }


    public function approve($id)
    {
        return $this->appointmentRequestService->approveRequest($id);
    }


    public function reject(RejectAppointmentRequestRequest $request, $id)
    {
        return $this->appointmentRequestService->rejectRequest($id, $request->input('reason'));
    }


    public function stats()
    {
        return $this->appointmentRequestService->getStats();
    }

    public function getIgnoredAppointmentRequests(Request $request)
    {
        return $this->appointmentRequestService->getIgnoredAppointmentRequests($request);
    }
}
