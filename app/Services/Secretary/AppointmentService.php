<?php

namespace App\Services\Secretary;

use App\Models\Appointment;
use App\Models\AppointmentRequest;
use App\Models\Doctor;
use App\Models\MedicalFile;
use App\Models\User;
use App\Models\WorkingHour;

use App\Traits\ApiResponseTrait;
use Carbon\Carbon;

class AppointmentService
{
    use ApiResponseTrait;

    public function getDoctorAppointments($doctorId)
    {
        $today = Carbon::today();
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', '>=', $today)
            ->where('status', '!=', 'deleted')
            ->orderBy('appointment_date')
            ->with(['user:id,full_name,email,phone'])
            ->get();

        return $this->unifiedResponse(true, 'Current appointments fetched successfully.', $appointments);
    }

    ///////////////////////////////////////////////////////////////////
    public function createAppointmentRequest($data)
    {
        $secretary = auth()->user()->secretaries()->first();
        $centerId = $secretary->center_id;
    
        $doctor = Doctor::find($data['doctor_id']);
    
        if (!$doctor) {
            return $this->unifiedResponse(false, 'Doctor not found.', [], [], 404);
        }
    
        $requestedDate = Carbon::parse($data['requested_date']);
        $dayOfWeek = $requestedDate->format('l');
        $workingHour = $doctor->workingHours()->where('day_of_week', $dayOfWeek)->first();
    
        if (!$workingHour) {
            return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 422);
        }
    
        $requestedTime = $requestedDate->format('H:i:s');
        if ($requestedTime < $workingHour->start_time || $requestedTime >= $workingHour->end_time) {
            return $this->unifiedResponse(false, 'Requested time is outside doctor\'s working hours.', [], [], 422);
        }
    
        $exists = AppointmentRequest::where('doctor_id', $data['doctor_id'])
            ->whereDate('requested_date', $requestedDate->format('Y-m-d'))
            ->whereTime('requested_date', $requestedTime)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();
    
        if ($exists) {
            return $this->unifiedResponse(false, 'Appointment request already exists for this doctor at this time.', [], [], 409);
        }
    
        $appointmentRequest = AppointmentRequest::create([
            'patient_id'     => $data['patient_id'],
            'doctor_id'      => $data['doctor_id'],
            'center_id'      => $centerId,
            'requested_date' => $data['requested_date'],
            'status'         => 'approved',
            'notes'          => $data['notes'] ?? null,
        ]);
    
        return $this->unifiedResponse(true, 'Appointment request created successfully.', $appointmentRequest);
    }
    
    
    ////////////////////////////////////////////////////////////////////////////
    

    public function updateAppointmentRequest($id, $data)
{
    $centerId = auth()->user()->secretaries->first()->center_id;

    $appointmentRequest = AppointmentRequest::where('id', $id)
        ->where('center_id', $centerId)
        ->first();

    if (!$appointmentRequest) {
        return $this->unifiedResponse(false, 'Appointment request not found.', [], [], 404);
    }

    $doctorId = $data['doctor_id'] ?? $appointmentRequest->doctor_id;
    $requestedDate = $data['requested_date'] ?? $appointmentRequest->requested_date;

    $appointmentDateTime = \Carbon\Carbon::parse($requestedDate);

    if ($appointmentDateTime->isPast()) {
        return $this->unifiedResponse(false, 'Cannot update appointment to a past date/time.', [], [], 422);
    }

    $dayOfWeek = $appointmentDateTime->format('l');
    $workingHour = WorkingHour::where('doctor_id', $doctorId)
        ->where('day_of_week', $dayOfWeek)
        ->first();

    if (!$workingHour) {
        return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 422);
    }

    $requestedTime = $appointmentDateTime->format('H:i:s');

    if ($requestedTime < $workingHour->start_time || $requestedTime >= $workingHour->end_time) {
        return $this->unifiedResponse(false, 'Requested time is outside doctor\'s working hours.', [], [], 422);
    }

    $exists = AppointmentRequest::where('doctor_id', $doctorId)
        ->whereDate('requested_date', $appointmentDateTime->toDateString())
        ->whereTime('requested_date', $appointmentDateTime->format('H:i:s'))
        ->where('id', '!=', $id)
        ->whereIn('status', ['pending', 'approved'])
        ->exists();

    if ($exists) {
        return $this->unifiedResponse(false, 'Another appointment already exists for this doctor at this time.', [], [], 409);
    }

    $appointmentRequest->update([
        'doctor_id' => $doctorId,
        'requested_date' => $appointmentDateTime,
        'notes' => $data['notes'] ?? $appointmentRequest->notes,
        'status' => $data['status'] ?? $appointmentRequest->status,
    ]);

    return $this->unifiedResponse(true, 'Appointment request updated successfully.', $appointmentRequest->fresh());
}

    

public function deleteAppointmentRequest($id)
{
    $centerId = auth()->user()->secretaries->first()->center_id;

    $appointmentRequest = AppointmentRequest::where('id', $id)
        ->where('center_id', $centerId)
        ->whereIn('status', ['pending', 'approved']) 
        ->first();

    if (!$appointmentRequest) {
        return $this->unifiedResponse(false, 'Appointment request not found or already processed.', [], [], 404);
    }

    $appointmentRequest->update(['status' => 'rejected']);

    return $this->unifiedResponse(true, 'Appointment request deleted (marked as rejected) successfully.');
}

public function confirmAttendance($id, $status)
{
    $allowed = ['present', 'absent'];
    if (!in_array($status, $allowed)) {
        return $this->unifiedResponse(false, 'Invalid attendance status. Allowed values: present, absent.', [], [], 422);
    }

    $centerId = auth()->user()->secretaries->first()->center_id;

    $appointmentRequest = AppointmentRequest::where('id', $id)
        ->where('center_id', $centerId)
        ->where('status', 'approved')
        ->first();

    if (!$appointmentRequest) {
        return $this->unifiedResponse(false, 'Appointment request not found or not approved.', [], [], 404);
    }

    $appointment = Appointment::create([
        'doctor_id'         => $appointmentRequest->doctor_id,
        'patient_id'        => $appointmentRequest->patient_id, 
        'appointment_date'  => $appointmentRequest->requested_date->format('Y-m-d'),
        'status'            => 'approved', 
        'booked_by'         => null, 
        'attendance_status' => $status,
        'notes'             => $appointmentRequest->notes,
    ]);

    $appointmentRequest->delete();

    return $this->unifiedResponse(true, 'Attendance confirmed and appointment created successfully.', $appointment);
}




    public function getDashboardStats()
    {
        $centerId = auth()->user()->secretaries->first()->center_id;

        $pendingAppointments = Appointment::whereHas('doctor', function($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->where('status', 'pending')->count();

        $newFiles = MedicalFile::whereDate('upload_date', now()->toDateString())
            ->whereHas('user.userCenters', function($q) use ($centerId) {
                $q->where('center_id', $centerId);
            })->count();

        $todaysAppointments = Appointment::whereHas('doctor', function($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->whereDate('appointment_date', now()->toDateString())->count();

        $totalPatients = User::whereHas('userCenters', function($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })->whereHas('roles', function($q) {
            $q->where('name', 'patient');
        })->count();
        return $this->unifiedResponse(true, 'Dashboard stats fetched successfully', [
            'pending_appointments' => $pendingAppointments,
            'new_files' => $newFiles,
            'todays_appointments' => $todaysAppointments,
            'total_patients' => $totalPatients,
        ]);
    }

    public function getTodaysAppointmentsForCenter()
{
    $centerId = auth()->user()->secretaries->first()->center_id;

    $appointmentRequests = AppointmentRequest::where('center_id', $centerId)
        ->whereDate('requested_date', now()->toDateString())
        ->with([
            'patient:id,full_name',
            'doctor.user:id,full_name',
            'doctor.doctorProfile.specialty:id,name'
        ])
        ->orderBy('requested_date')
        ->get()
        ->map(function($request) {
            return [
                'id' => $request->id,
                'status' => $request->status,
                'time' => $request->requested_date ? $request->requested_date->format('H:i') : null,
                'patient_name' => $request->patient->full_name ?? null,
                'doctor_name' => $request->doctor->user->full_name ?? null,
                'specialty' => $request->doctor->doctorProfile->specialty->name ?? null,
                'notes' => $request->notes,
            ];
        });

    return $this->unifiedResponse(true, 'Today\'s appointment requests fetched successfully', $appointmentRequests);
}


}
