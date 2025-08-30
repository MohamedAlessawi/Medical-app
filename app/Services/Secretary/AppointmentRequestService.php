<?php

namespace App\Services\Secretary;

use App\Models\{AppointmentRequest, Appointment, User};
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AppointmentRequestService
{
    use ApiResponseTrait;


    public function getAppointmentRequests(Request $request)
    {
        $centerId = auth()->user()->secretaries->first()->center_id;

        $query = AppointmentRequest::where('center_id', $centerId)
            ->where('status', '!=', 'deleted')
            ->with(['patient', 'doctor.user.doctorProfile.specialty', 'center']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('requested_date', $request->date);
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'patient_name' => $request->patient_name,
                    'patient_phone' => $request->patient->phone,
                    'doctor_name' => $request->doctor_name,
                    'specialty' => $request->specialty_name,
                    'center_name' => $request->center_name,
                    'requested_date' => $request->requested_date_formatted,
                    'requested_time' => $request->requested_time_formatted,
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'created_at' => $request->created_at_formatted,
                ];
            });

        return $this->unifiedResponse(true, 'Appointment requests fetched successfully.', $requests);
    }


    public function getAppointmentRequest($id)
    {
        $centerId = auth()->user()->secretaries->first()->center_id;

        $request = AppointmentRequest::where('id', $id)
            ->where('center_id', $centerId)
            ->with(['patient', 'doctor.user.doctorProfile.specialty', 'center'])
            ->first();

        if (!$request) {
            return $this->unifiedResponse(false, 'Appointment request not found.', [], [], 404);
        }

        return $this->unifiedResponse(true, 'Appointment request details fetched successfully.', $request);
    }


    public function approveRequest($id)
    {
        $centerId = auth()->user()->secretaries->first()->center_id;
    
        $appointmentRequest = AppointmentRequest::where('id', $id)
            ->where('center_id', $centerId)
            ->where('status', 'pending')
            ->first();
    
        if (!$appointmentRequest) {
            return $this->unifiedResponse(false, 'Appointment request not found or already processed.', [], [], 404);
        }
    
        // تحقق إذا في طلب مؤكد بنفس الوقت مع نفس الدكتور
        $conflictExists = AppointmentRequest::where('doctor_id', $appointmentRequest->doctor_id)
            ->whereDate('requested_date', $appointmentRequest->requested_date->format('Y-m-d'))
            ->whereTime('requested_date', $appointmentRequest->requested_date->format('H:i:s'))
            ->where('status', 'approved')
            ->exists();
    
        if ($conflictExists) {
            return $this->unifiedResponse(false, 'This time slot is no longer available.', [], [], 409);
        }
    
        // تحديث نفس السطر
        $appointmentRequest->update([
            'status' => 'approved'
        ]);
    
        return $this->unifiedResponse(true, 'Appointment request approved successfully.', [
            'appointment_request_id' => $appointmentRequest->id,
            'status' => $appointmentRequest->status,
        ]);
    }
    


    public function rejectRequest($id, $reason = null)
    {
        $centerId = auth()->user()->secretaries->first()->center_id;

        $appointmentRequest = AppointmentRequest::where('id', $id)
            ->where('center_id', $centerId)
            ->where('status', 'pending')
            ->first();

        if (!$appointmentRequest) {
            return $this->unifiedResponse(false, 'Appointment request not found or already processed.', [], [], 404);
        }


        $appointmentRequest->update([
            'status' => 'rejected',
            'notes' => $appointmentRequest->notes . "\nRejection reason: " . ($reason ?? 'No reason provided'),
        ]);

        return $this->unifiedResponse(true, 'Appointment request rejected successfully.');
    }


    public function getStats()
    {
        $centerId = auth()->user()->secretaries->first()->center_id;

        $stats = [
            'total_requests' => AppointmentRequest::where('center_id', $centerId)->where('status', '!=', 'deleted')->count(),
            'pending_requests' => AppointmentRequest::where('center_id', $centerId)->where('status', 'pending')->count(),
            'approved_requests' => AppointmentRequest::where('center_id', $centerId)->where('status', 'approved')->count(),
            'rejected_requests' => AppointmentRequest::where('center_id', $centerId)->where('status', 'rejected')->count(),
            'today_requests' => AppointmentRequest::where('center_id', $centerId)
                ->where('status', '!=', 'deleted')
                ->whereDate('created_at', Carbon::today())->count(),
        ];

        return $this->unifiedResponse(true, 'Appointment request stats fetched successfully.', $stats);
    }
}
