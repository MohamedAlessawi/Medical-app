<?php

namespace App\Services\Doctor;

use App\Repositories\Doctor\AppointmentRepository;
use Illuminate\Support\Facades\Auth;
use App\Traits\ApiResponseTrait;
use App\Models\Appointment;
use Illuminate\Support\Carbon;


class DoctorAppointmentService
{
    use ApiResponseTrait;

    protected $appointmentRepo;

    public function __construct(AppointmentRepository $appointmentRepo)
    {
        $this->appointmentRepo = $appointmentRepo;
    }

    public function getDoctorAppointments($request)
    {
        $doctor = Auth::user()->doctor;

        if (!$doctor) {
            return $this->unifiedResponse(false, 'You are not linked to a center as a doctor.', [], [], 403);
        }

        $filter = $request->query('filter');
        $perPage = $request->query('per_page', 10);

        $appointments = $this->appointmentRepo
            ->getConfirmedAppointmentsByDoctor($doctor->id, $filter, $perPage);

        return $this->unifiedResponse(true, 'Appointments fetched successfully.', $appointments);
    }
    public function showAppointment($id)
    {
        $doctor = Auth::user()->doctor;

        $appointment = Appointment::with('user:id,full_name,email,phone')
            ->where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->where('status', 'confirmed')
            ->first();

        if (!$appointment) {
            return $this->unifiedResponse(false, 'Appointment not found.', [], [], 404);
        }

        return $this->unifiedResponse(true, 'Appointment details.', $appointment);
    }
    public function getPastAppointments($request)
{
    $doctor = Auth::user()->doctor;

    if (!$doctor) {
        return $this->unifiedResponse(false, 'You are not linked to a center as a doctor.', [], [], 403);
    }

    $query = Appointment::with('user:id,full_name,email,phone')
        ->where('doctor_id', $doctor->id)
        ->where('status', 'confirmed')
        ->where('appointment_date', '<', now());

    if ($request->has('date')) {
        $query->whereDate('appointment_date', $request->query('date'));
    }

    if ($request->has('patient_id')) {
        $query->where('booked_by', $request->query('patient_id'));
    }

    $perPage = $request->query('per_page', 10);

    $appointments = $query->orderByDesc('appointment_date')->paginate($perPage);

    return $this->unifiedResponse(true, 'Past appointments fetched successfully.', $appointments);
}

    // public function confirmAttendance($id, $data)
    // {
    //     $doctor = Auth::user()->doctor;

    //     $appointment = Appointment::where('id', $id)
    //         ->where('doctor_id', $doctor->id)
    //         ->where('status', 'confirmed')
    //         ->first();

    //     if (!$appointment) {
    //         return $this->unifiedResponse(false, 'Appointment not found or unauthorized.', [], [], 404);
    //     }

    //     if (now()->lt($appointment->appointment_date)) {
    //         return $this->unifiedResponse(false, 'You cannot confirm attendance before the appointment time.', [], [], 403);
    //     }

    //     $validatedStatus = $data['attendance_status'] ?? null;
    //     if (!in_array($validatedStatus, ['present', 'absent'])) {
    //         return $this->unifiedResponse(false, 'Invalid attendance status.', [], [], 422);
    //     }

    //     $appointment->attendance_status = $validatedStatus;
    //     $appointment->notes = $data['notes'] ?? null;
    //     $appointment->save();

    //     return $this->unifiedResponse(true, 'Attendance confirmed.', $appointment);
    // }
  public function getPastVisitsForPatient($patientId)
{
    $doctor = Auth::user()->doctor;

    if (!$doctor) {
        return $this->unifiedResponse(false, 'You are not linked to a center as a doctor.', [], [], 403);
    }

    $hasRelation = Appointment::where('doctor_id', $doctor->id)
        ->where('booked_by', $patientId)
        ->exists();

    if (!$hasRelation) {
        return $this->unifiedResponse(false, 'You have no appointment history with this patient.', [], [], 403);
    }

    $appointments = Appointment::with('user:id,full_name,email,phone')
        ->where('doctor_id', $doctor->id)
        ->where('booked_by', $patientId)
        ->where('status', 'confirmed')
        ->where('appointment_date', '<', now())
        ->orderByDesc('appointment_date')
        ->get();

    return $this->unifiedResponse(true, 'Past visits for this patient fetched successfully.', $appointments);
}


}

