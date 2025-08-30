<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Services\Secretary\DoctorService;
use App\Http\Requests\Secretary\WorkingHourRequest;
use Illuminate\Http\Request;
use App\Services\Secretary\AppointmentService;

class DoctorController extends Controller
{
    protected $doctorService;
    protected $appointmentService;

    public function __construct(DoctorService $doctorService, AppointmentService $appointmentService)
    {
        $this->doctorService = $doctorService;
        $this->appointmentService = $appointmentService;
    }

    public function index()
    {
        return $this->doctorService->getDoctorsInCenter();
    }

    public function show($id)
    {
        return $this->doctorService->getDoctorDetails($id);
    }

    public function getWorkingHours($id)
    {
        return $this->doctorService->getWorkingHours($id);
    }

    public function storeWorkingHour(WorkingHourRequest $request, $id)
    {
        return $this->doctorService->addWorkingHour($id, $request->validated());
    }

    public function updateWorkingHour(WorkingHourRequest $request, $id)
    {
        return $this->doctorService->updateWorkingHour($id, $request->validated());
    }

    public function deleteWorkingHour($id)
    {
        return $this->doctorService->deleteWorkingHour($id);
    }

    public function search(Request $request)
    {
        return $this->doctorService->searchDoctors($request->query('query'));
    }

    public function getAppointments($id)
    {
        return $this->appointmentService->getDoctorAppointments($id);
    }

    public function bookAppointment(Request $request)
    {
        $data = $request->validate([
            'doctor_id'      => 'required|exists:doctors,id',
            'patient_id'     => 'required|exists:users,id',
            'requested_date' => 'required|date_format:Y-m-d H:i:s|after:now',
            'notes'          => 'nullable|string',
        ]);

        return $this->appointmentService->createAppointmentRequest($data);
    }

    public function updateAppointment(Request $request, $id)
    {
        $data = $request->only(['doctor_id', 'requested_date', 'patient_id', 'notes']);
        return $this->appointmentService->updateAppointmentRequest($id, $data);
    }

    public function deleteAppointment($id)
    {
        return $this->appointmentService->deleteAppointmentRequest($id);
    }

    public function confirmAttendance(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:present,absent',
        ]);

        return $this->appointmentService->confirmAttendance($id, $request->status);
    }

    public function dashboardStats()
    {
        return $this->appointmentService->getDashboardStats();
    }

    public function todaysAppointmentsForCenter()
    {
        return $this->appointmentService->getTodaysAppointmentsForCenter();
    }
}

