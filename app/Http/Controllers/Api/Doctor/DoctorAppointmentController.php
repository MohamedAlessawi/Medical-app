<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorAppointmentService;
use Illuminate\Http\Request;

class DoctorAppointmentController extends Controller
{
    protected $appointmentService;

    public function __construct(DoctorAppointmentService $appointmentService)
    {
        $this->appointmentService = $appointmentService;
    }

    public function index(Request $request)
    {
        return $this->appointmentService->getDoctorAppointments($request);
    }
    public function show($id)
    {
        return $this->appointmentService->showAppointment($id);
    }

    // public function confirmAttendance(Request $request, $id)
    // {
    //     return $this->appointmentService->confirmAttendance($id, $request->all());
    // }
    public function pastAppointments(Request $request)
{
    return $this->appointmentService->getPastAppointments($request);
}

}
