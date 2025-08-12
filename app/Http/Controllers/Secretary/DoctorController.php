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
        $data = $request->only(['doctor_id', 'appointment_date', 'appointment_time', 'patient_id', 'status', 'attendance_status', 'notes']);
        return $this->appointmentService->createAppointment($data);
    }

    public function updateAppointment(Request $request, $id)
    {
        $data = $request->only(['doctor_id', 'appointment_date', 'appointment_time', 'booked_by', 'status', 'attendance_status', 'notes']);
        return $this->appointmentService->updateAppointment($id, $data);
    }

    public function deleteAppointment($id)
    {
        return $this->appointmentService->deleteAppointment($id);
    }

    public function confirmAttendance(Request $request, $id)
    {
        $status = $request->input('attendance_status');
        return $this->appointmentService->confirmAttendance($id, $status);
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

