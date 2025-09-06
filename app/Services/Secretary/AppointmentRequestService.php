<?php

namespace App\Services\Secretary;

use App\Models\{AppointmentRequest, Appointment, User, UserCenter};
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\Notification;


class AppointmentRequestService
{
    use ApiResponseTrait;


    public function getAppointmentRequests(Request $request)
    {
        // $centerId = auth()->user()->secretaries->first()->center_id;

        $user = auth()->user();

        $centerId = optional($user->adminCenters->first())->center_id
                ?? optional($user->secretaries->first())->center_id;

        if (!$centerId) {
            return $this->unifiedResponse(true, 'Appointment requests fetched successfully.', collect());
        }

        $query = AppointmentRequest::where('center_id', $centerId)
            ->where('status', '!=', 'deleted')
            ->whereDate('requested_date', '>=', now()->toDateString())
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

        // تاريخ ووقت الطلب المقبول
        $dt   = $appointmentRequest->requested_date instanceof \Carbon\Carbon
            ? $appointmentRequest->requested_date
            : \Carbon\Carbon::parse($appointmentRequest->requested_date);
        $date = $dt->toDateString();
        $time = $dt->format('H:i:s');

        // سنحتاج IDs المرضى المتضاربين لنرسل إشعارات بعد الـcommit
        $conflictingPatientIds = [];

        DB::transaction(function () use ($appointmentRequest, $centerId, $date, $time, &$conflictingPatientIds) {
            // 1) قبول الطلب الحالي
            $appointmentRequest->update(['status' => 'approved']);

            // 2) ربط المريض بالمركز (آخر زيارة = تاريخ الطلب)
            $patientId = $appointmentRequest->patient_id ?? ($appointmentRequest->patient?->id);
            if ($patientId) {
                $this->linkPatientToCenter($patientId, $centerId, $appointmentRequest->requested_date);
            }

            // 3) رفض كل الطلبات المتضاربة (نفس الطبيب + التاريخ + الوقت) ما عدا الحالي
            $conflicting = AppointmentRequest::where('doctor_id', $appointmentRequest->doctor_id)
                ->where('center_id', $centerId)
                ->whereDate('requested_date', $date)
                ->whereTime('requested_date', $time)
                ->whereIn('status', ['pending', 'approved'])
                ->where('id', '!=', $appointmentRequest->id)
                ->get();

            foreach ($conflicting as $other) {
                $other->update([
                    'status' => 'rejected',
                    'notes'  => trim(($other->notes ?? '') . "\nAuto-rejected: time slot already booked."),
                ]);
                if ($other->patient_id) {
                    $conflictingPatientIds[] = (int) $other->patient_id;
                }
            }
        });

        // ===== إشعارات بعد نجاح العملية =====

        // للمريض المقبول
        Notification::pushToUser(
            userId: (int) $appointmentRequest->patient_id,
            centerId: (int) $centerId,
            title: 'Booking confirmed',
            message: "Your appointment request on {$date} at {$time} has been approved."
        );

        // للدكتور
        $doctorUserId = (int) \DB::table('doctors')->where('id', $appointmentRequest->doctor_id)->value('user_id');
        if ($doctorUserId) {
            Notification::pushToUser(
                userId: $doctorUserId,
                centerId: (int) $centerId,
                title: 'New appointment',
                message: "You have a new approved booking on {$date} at {$time}."
            );
        }

        // لكل المرضى أصحاب الطلبات المتضاربة التي رُفضت
        if (!empty($conflictingPatientIds)) {
            Notification::pushToUsers(
                userIds: $conflictingPatientIds,
                centerId: (int) $centerId,
                title: 'Booking rejected',
                message: "Your request was rejected because the time slot has been booked. Please choose another time."
            );
        }

        return $this->unifiedResponse(true, 'Appointment request approved successfully.', [
            'appointment_request_id' => $appointmentRequest->id,
            'status'                 => $appointmentRequest->status,
        ]);
    }



    // public function approveRequest($id)
    // {
    //     $centerId = auth()->user()->secretaries->first()->center_id;

    //     $appointmentRequest = AppointmentRequest::where('id', $id)
    //         ->where('center_id', $centerId)
    //         ->where('status', 'pending')
    //         ->first();

    //     if (!$appointmentRequest) {
    //         return $this->unifiedResponse(false, 'Appointment request not found or already processed.', [], [], 404);
    //     }

    //     $conflictExists = AppointmentRequest::where('doctor_id', $appointmentRequest->doctor_id)
    //         ->whereDate('requested_date', $appointmentRequest->requested_date->format('Y-m-d'))
    //         ->whereTime('requested_date', $appointmentRequest->requested_date->format('H:i:s'))
    //         ->where('status', 'approved')
    //         ->exists();

    //     if ($conflictExists) {
    //         return $this->unifiedResponse(false, 'This time slot is no longer available.', [], [], 409);
    //     }

    //     // $appointmentRequest->update([
    //     //     'status' => 'approved'
    //     // ]);
    //     DB::transaction(function () use ($appointmentRequest, $centerId) {
    //         // 1) غيّر حالة الطلب لمقبول
    //         $appointmentRequest->update(['status' => 'approved']);

    //         // 2) اربط المريض بالمركز في user_centers
    //         // نفترض وجود عمود patient_id على الطلب؛ إن لم يكن، استخدم $appointmentRequest->patient->id
    //         $patientId = $appointmentRequest->patient_id ?? ($appointmentRequest->patient?->id);

    //         if ($patientId) {
    //             $this->linkPatientToCenter($patientId, $centerId, $appointmentRequest->requested_date);
    //         }
    //     });

    //     return $this->unifiedResponse(true, 'Appointment request approved successfully.', [
    //         'appointment_request_id' => $appointmentRequest->id,
    //         'status' => $appointmentRequest->status,
    //     ]);
    // }


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
            'notes'  => trim(($appointmentRequest->notes ?? '') . "\nRejection reason: " . ($reason ?? 'No reason provided')),
        ]);

        // إشعار للمريض
        if ($appointmentRequest->patient_id) {
            Notification::pushToUser(
                userId: (int) $appointmentRequest->patient_id,
                centerId: (int) $centerId,
                title: 'Booking rejected',
                message: 'Your appointment request was rejected. Please choose another time.'
            );
        }

        return $this->unifiedResponse(true, 'Appointment request rejected successfully.');
    }


    // public function rejectRequest($id, $reason = null)
    // {
    //     $centerId = auth()->user()->secretaries->first()->center_id;

    //     $appointmentRequest = AppointmentRequest::where('id', $id)
    //         ->where('center_id', $centerId)
    //         ->where('status', 'pending')
    //         ->first();

    //     if (!$appointmentRequest) {
    //         return $this->unifiedResponse(false, 'Appointment request not found or already processed.', [], [], 404);
    //     }


    //     $appointmentRequest->update([
    //         'status' => 'rejected',
    //         'notes' => $appointmentRequest->notes . "\nRejection reason: " . ($reason ?? 'No reason provided'),
    //     ]);

    //     return $this->unifiedResponse(true, 'Appointment request rejected successfully.');
    // }


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

    ///////////////////////////////////////////////////////////////

    public function getIgnoredAppointmentRequests(Request $request)
    {
        $centerId = auth()->user()->secretaries->first()->center_id;

        $requests = AppointmentRequest::where('center_id', $centerId)
            ->where('status', 'pending')
            ->whereDate('requested_date', '<', now()->toDateString())
            ->with(['patient', 'doctor.user.doctorProfile.specialty', 'center'])
            ->orderBy('requested_date', 'asc')
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

        return $this->unifiedResponse(true, 'Ignored appointment requests fetched successfully.', $requests);
    }


    private function ensurePatientRole(int $userId): void
    {
        $hasRole = DB::table('user_roles')
            ->join('roles','roles.id','=','user_roles.role_id')
            ->where('user_roles.user_id', $userId)
            ->where('roles.name', 'patient')
            ->exists();

        if (!$hasRole) {
            $patientRoleId = (int) DB::table('roles')->where('name','patient')->value('id');
            if ($patientRoleId) {
                DB::table('user_roles')->insert([
                    'user_id' => $userId,
                    'role_id' => $patientRoleId,
                    // 'created_at' => now(),
                    // 'updated_at' => now(),
                ]);
            }
        }
    }

    private function linkPatientToCenter(int $userId, int $centerId, ?\Carbon\Carbon $lastVisit = null): void
    {
        $this->ensurePatientRole($userId);

        $exists = UserCenter::where('user_id', $userId)
            ->where('center_id', $centerId)
            ->exists();

        if (!$exists) {
            UserCenter::create([
                'user_id'   => $userId,
                'center_id' => $centerId,
                'condition' => null,
                'last_visit'=> $lastVisit ? $lastVisit->toDateString() : now()->toDateString(),
                'status'    => "Active",
            ]);
        } else {
            UserCenter::where('user_id', $userId)
                ->where('center_id', $centerId)
                ->update([
                    'last_visit' => $lastVisit ? $lastVisit->toDateString() : now()->toDateString(),
                ]);
        }
    }


}
