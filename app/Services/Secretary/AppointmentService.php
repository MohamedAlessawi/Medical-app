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
use App\Models\Notification;
use Illuminate\Support\Facades\DB;



class AppointmentService
{
    use ApiResponseTrait;

    public function getDoctorAppointments($doctorId)
    {
        $today = Carbon::today()->toDateString();

        $appointments = \App\Models\Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $today)
            ->whereIn('attendance_status', ['present', 'absent'])
            ->where('status', '!=', 'deleted')
            ->with([
                'patient:id,full_name,phone',
                'user:id,full_name,phone'
            ])
            ->orderBy('appointment_time')
            ->get()
            ->map(function ($a) {
                $patientName  = $a->patient->full_name ?? $a->user->full_name ?? null;
                $patientPhone = $a->patient->phone      ?? $a->user->phone      ?? null;

                return [
                    'id'                 => $a->id,
                    'patient_id'         => $a->patient_id,
                    'patient_name'       => $patientName,
                    'patient_phone'      => $patientPhone,
                    'appointment_date'   => $a->appointment_date ? \Carbon\Carbon::parse($a->appointment_date)->toDateString() : null,
                    'appointment_time'   => $a->appointment_time ?? null,
                    'attendance_status'  => $a->attendance_status,
                    'status'             => $a->status,
                    'notes'              => $a->notes,
                ];
            });

        $approvedRequests = \App\Models\AppointmentRequest::where('doctor_id', $doctorId)
            ->whereDate('requested_date', $today)
            ->where('status', 'approved')
            ->with([
                'patient:id,full_name,phone'
            ])
            ->orderBy('requested_date')
            ->get()
            ->map(function ($r) {
                return [
                    'id'            => $r->id,
                    'patient_id'    => $r->patient_id,
                    'patient_name'  => $r->patient->full_name ?? null,
                    'patient_phone' => $r->patient->phone ?? null,
                    'requested_time'=> $r->requested_date ? Carbon::parse($r->requested_date)->format('H:i') : null,
                    'status'        => $r->status,
                    'notes'         => $r->notes,
                ];
            });

        return $this->unifiedResponse(true, 'Doctor appointments & approved requests for today fetched.', [
            'appointments_today'        => $appointments,
            'approved_requests_today'   => $approvedRequests,
        ]);
    }


    // public function getDoctorAppointments($doctorId)
    // {
    //     $today = Carbon::today();
    //     $appointments = Appointment::where('doctor_id', $doctorId)
    //         ->whereDate('appointment_date', '>=', $today)
    //         ->where('status', '!=', 'deleted')
    //         ->orderBy('appointment_date')
    //         ->with(['user:id,full_name,email,phone'])
    //         ->get();

    //     return $this->unifiedResponse(true, 'Current appointments fetched successfully.', $appointments);
    // }

    ///////////////////////////////////////////////////////////////////

    public function createAppointmentRequest($data)
    {
        $secretary = auth()->user()->secretaries()->first();
        $centerId  = $secretary->center_id;

        $doctor = Doctor::find($data['doctor_id']);
        if (!$doctor) {
            return $this->unifiedResponse(false, 'Doctor not found.', [], [], 404);
        }

        $requestedDate = Carbon::parse($data['requested_date']);
        $dayOfWeek     = $requestedDate->format('l');
        $workingHour   = $doctor->workingHours()->where('day_of_week', $dayOfWeek)->first();

        if (!$workingHour) {
            return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 422);
        }

        $requestedTime = $requestedDate->format('H:i:s');
        if ($requestedTime < $workingHour->start_time || $requestedTime >= $workingHour->end_time) {
            return $this->unifiedResponse(false, 'Requested time is outside doctor\'s working hours.', [], [], 422);
        }

        $date = $requestedDate->toDateString();
        $time = $requestedTime;

        $conflictingPatientIds = [];
        $appointmentRequest = null;

        \DB::transaction(function () use ($data, $centerId, $date, $time, &$appointmentRequest, &$conflictingPatientIds) {
            // أنشئ الطلب بحالة approved
            $appointmentRequest = AppointmentRequest::create([
                'patient_id'     => $data['patient_id'],
                'doctor_id'      => $data['doctor_id'],
                'center_id'      => $centerId,
                'requested_date' => "{$date} {$time}",
                'status'         => 'approved',
                'notes'          => $data['notes'] ?? null,
            ]);

            // اربط المريض بالمركز
            // (خيار: إذا عندك طريقة جاهزة لذلك داخل Service ثانية استدعيها)
            $patientId = (int) $data['patient_id'];
            $ucExists = \App\Models\UserCenter::where('user_id', $patientId)->where('center_id', $centerId)->exists();
            if (!$ucExists) {
                \App\Models\UserCenter::create([
                    'user_id'   => $patientId,
                    'center_id' => $centerId,
                    'status'    => 'Active',
                    'last_visit'=> $date,
                ]);
            } else {
                \App\Models\UserCenter::where('user_id', $patientId)->where('center_id', $centerId)->update(['last_visit' => $date]);
            }

            // ارفض الطلبات المتضاربة
            $conflicting = AppointmentRequest::where('doctor_id', $data['doctor_id'])
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

        // إشعار للمريض المقبول
        Notification::pushToUser(
            userId: (int) $appointmentRequest->patient_id,
            centerId: (int) $centerId,
            title: 'Booking confirmed',
            message: "Your appointment request on {$date} at {$time} has been approved."
        );

        // إشعار للدكتور
        $doctorUserId = (int) \DB::table('doctors')->where('id', $appointmentRequest->doctor_id)->value('user_id');
        if ($doctorUserId) {
            Notification::pushToUser(
                userId: $doctorUserId,
                centerId: (int) $centerId,
                title: 'New appointment',
                message: "You have a new approved booking on {$date} at {$time}."
            );
        }

        // إشعارات مرفوضين (لو وجدوا)
        if (!empty($conflictingPatientIds)) {
            Notification::pushToUsers(
                userIds: $conflictingPatientIds,
                centerId: (int) $centerId,
                title: 'Booking rejected',
                message: "Your request was rejected because the time slot has been booked. Please choose another time."
            );
        }

        return $this->unifiedResponse(true, 'Appointment request created successfully.', $appointmentRequest);
    }

    // public function createAppointmentRequest($data)
    // {
    //     $secretary = auth()->user()->secretaries()->first();
    //     $centerId = $secretary->center_id;

    //     $doctor = Doctor::find($data['doctor_id']);

    //     if (!$doctor) {
    //         return $this->unifiedResponse(false, 'Doctor not found.', [], [], 404);
    //     }

    //     $requestedDate = Carbon::parse($data['requested_date']);
    //     $dayOfWeek = $requestedDate->format('l');
    //     $workingHour = $doctor->workingHours()->where('day_of_week', $dayOfWeek)->first();

    //     if (!$workingHour) {
    //         return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 422);
    //     }

    //     $requestedTime = $requestedDate->format('H:i:s');
    //     if ($requestedTime < $workingHour->start_time || $requestedTime >= $workingHour->end_time) {
    //         return $this->unifiedResponse(false, 'Requested time is outside doctor\'s working hours.', [], [], 422);
    //     }

    //     $exists = AppointmentRequest::where('doctor_id', $data['doctor_id'])
    //         ->whereDate('requested_date', $requestedDate->format('Y-m-d'))
    //         ->whereTime('requested_date', $requestedTime)
    //         ->whereIn('status', ['pending', 'approved'])
    //         ->exists();

    //     if ($exists) {
    //         return $this->unifiedResponse(false, 'Appointment request already exists for this doctor at this time.', [], [], 409);
    //     }

    //     $appointmentRequest = AppointmentRequest::create([
    //         'patient_id'     => $data['patient_id'],
    //         'doctor_id'      => $data['doctor_id'],
    //         'center_id'      => $centerId,
    //         'requested_date' => $data['requested_date'],
    //         'status'         => 'approved',
    //         'notes'          => $data['notes'] ?? null,
    //     ]);

    //     return $this->unifiedResponse(true, 'Appointment request created successfully.', $appointmentRequest);
    // }


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

        $appointmentDateTime = Carbon::parse($requestedDate);

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

        // هل في طلب آخر متضارب؟
        $exists = AppointmentRequest::where('doctor_id', $doctorId)
            ->whereDate('requested_date', $appointmentDateTime->toDateString())
            ->whereTime('requested_date', $requestedTime)
            ->where('id', '!=', $id)
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($exists && (($data['status'] ?? $appointmentRequest->status) !== 'approved')) {
            // السماح بالـ exists فقط لو رح نوافق هالطلب ونرفض الباقي
            return $this->unifiedResponse(false, 'Another appointment already exists for this doctor at this time.', [], [], 409);
        }

        // خزّن القيم القديمة للمقارنة بعد التحديث
        $oldDoctorId = $appointmentRequest->doctor_id;
        $oldDT       = $appointmentRequest->requested_date instanceof Carbon
                        ? $appointmentRequest->requested_date->copy()
                        : Carbon::parse($appointmentRequest->requested_date);
        $oldStatus   = $appointmentRequest->status;

        $targetStatus = $data['status'] ?? $appointmentRequest->status;

        // حدّث الطلب
        $appointmentRequest->update([
            'doctor_id'      => $doctorId,
            'requested_date' => $appointmentDateTime,
            'notes'          => $data['notes'] ?? $appointmentRequest->notes,
            'status'         => $targetStatus,
        ]);

        // بعد التحديث: منطق الإشعارات حسب الحالة
        $date = $appointmentDateTime->toDateString();
        $time = $appointmentDateTime->format('H:i:s');

        // إذا صار Approved: اربط المريض بالمركز + ارفض المتضاربين + إشعارات
        if ($targetStatus === 'approved') {
            $conflictingPatientIds = [];

            DB::transaction(function () use ($appointmentRequest, $centerId, $date, $time, &$conflictingPatientIds) {
                // ربط المريض بالمركز
                $patientId = (int) $appointmentRequest->patient_id;
                if ($patientId) {
                    $this->linkPatientToCenter($patientId, $centerId, Carbon::parse($appointmentRequest->requested_date));
                }

                // ارفض الطلبات المتضاربة الأخرى
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

            // إشعار للمريض المقبول
            if ($appointmentRequest->patient_id) {
                Notification::pushToUser(
                    userId: (int) $appointmentRequest->patient_id,
                    centerId: (int) $centerId,
                    title: 'Booking confirmed',
                    message: "Your appointment request on {$date} at {$time} has been approved."
                );
            }

            // إشعار للدكتور
            $doctorUserId = (int) DB::table('doctors')->where('id', $appointmentRequest->doctor_id)->value('user_id');
            if ($doctorUserId) {
                Notification::pushToUser(
                    userId: $doctorUserId,
                    centerId: (int) $centerId,
                    title: 'New appointment',
                    message: "You have a new approved booking on {$date} at {$time}."
                );
            }

            // إشعارات للمتضاربين
            if (!empty($conflictingPatientIds)) {
                Notification::pushToUsers(
                    userIds: $conflictingPatientIds,
                    centerId: (int) $centerId,
                    title: 'Booking rejected',
                    message: "Your request was rejected because the time slot has been booked. Please choose another time."
                );
            }

            return $this->unifiedResponse(true, 'Appointment request approved and conflicts handled.', $appointmentRequest->fresh());
        }

        // إذا صار Rejected: إشعار للمريض
        if ($targetStatus === 'rejected' && $oldStatus !== 'rejected') {
            if ($appointmentRequest->patient_id) {
                Notification::pushToUser(
                    userId: (int) $appointmentRequest->patient_id,
                    centerId: (int) $centerId,
                    title: 'Booking rejected',
                    message: 'Your appointment request was rejected. Please choose another time.'
                );
            }
        }

        // لو بقي Pending وتغيّر الوقت/الطبيب → أعطي المريض إشعار “تم تحديث الطلب”
        $changed = ($oldDoctorId !== $doctorId) ||
                ($oldDT->toDateTimeString() !== $appointmentDateTime->toDateTimeString());

        if ($targetStatus === 'pending' && $changed && $appointmentRequest->patient_id) {
            Notification::pushToUser(
                userId: (int) $appointmentRequest->patient_id,
                centerId: (int) $centerId,
                title: 'Request updated',
                message: "Your booking request has been updated to {$date} at {$time}."
            );
        }

        return $this->unifiedResponse(true, 'Appointment request updated successfully.', $appointmentRequest->fresh());
    }


//     public function updateAppointmentRequest($id, $data)
// {
//     $centerId = auth()->user()->secretaries->first()->center_id;

//     $appointmentRequest = AppointmentRequest::where('id', $id)
//         ->where('center_id', $centerId)
//         ->first();

//     if (!$appointmentRequest) {
//         return $this->unifiedResponse(false, 'Appointment request not found.', [], [], 404);
//     }

//     $doctorId = $data['doctor_id'] ?? $appointmentRequest->doctor_id;
//     $requestedDate = $data['requested_date'] ?? $appointmentRequest->requested_date;

//     $appointmentDateTime = \Carbon\Carbon::parse($requestedDate);

//     if ($appointmentDateTime->isPast()) {
//         return $this->unifiedResponse(false, 'Cannot update appointment to a past date/time.', [], [], 422);
//     }

//     $dayOfWeek = $appointmentDateTime->format('l');
//     $workingHour = WorkingHour::where('doctor_id', $doctorId)
//         ->where('day_of_week', $dayOfWeek)
//         ->first();

//     if (!$workingHour) {
//         return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 422);
//     }

//     $requestedTime = $appointmentDateTime->format('H:i:s');

//     if ($requestedTime < $workingHour->start_time || $requestedTime >= $workingHour->end_time) {
//         return $this->unifiedResponse(false, 'Requested time is outside doctor\'s working hours.', [], [], 422);
//     }

//     $exists = AppointmentRequest::where('doctor_id', $doctorId)
//         ->whereDate('requested_date', $appointmentDateTime->toDateString())
//         ->whereTime('requested_date', $appointmentDateTime->format('H:i:s'))
//         ->where('id', '!=', $id)
//         ->whereIn('status', ['pending', 'approved'])
//         ->exists();

//     if ($exists) {
//         return $this->unifiedResponse(false, 'Another appointment already exists for this doctor at this time.', [], [], 409);
//     }

//     $appointmentRequest->update([
//         'doctor_id' => $doctorId,
//         'requested_date' => $appointmentDateTime,
//         'notes' => $data['notes'] ?? $appointmentRequest->notes,
//         'status' => $data['status'] ?? $appointmentRequest->status,
//     ]);

//     return $this->unifiedResponse(true, 'Appointment request updated successfully.', $appointmentRequest->fresh());
// }


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

        // احفظ الحالة القديمة قبل التعديل
        $prevStatus = $appointmentRequest->status;

        // نفّذ الإلغاء
        $appointmentRequest->update(['status' => 'rejected']);

        // جهّز التاريخ/الوقت
        $dt = $appointmentRequest->requested_date instanceof Carbon
            ? $appointmentRequest->requested_date
            : Carbon::parse($appointmentRequest->requested_date);

        // إشعار للمريض
        if ($appointmentRequest->patient_id) {
            Notification::pushToUser(
                userId: (int) $appointmentRequest->patient_id,
                centerId: (int) $centerId,
                title: 'Booking cancelled',
                message: 'Your appointment request has been cancelled. Please select another time.'
            );
        }

        // إذا كان الطلب Approved سابقًا: أشعر الطبيب + اعمل broadcast للمرضى الآخرين
        if ($prevStatus === 'approved') {
            $doctorId     = (int) $appointmentRequest->doctor_id;
            $doctorUserId = (int) DB::table('doctors')->where('id', $doctorId)->value('user_id');
            $doctorName   = (string) DB::table('users')->where('id', $doctorUserId)->value('full_name') ?: 'Doctor';

            // إشعار للطبيب
            if ($doctorUserId) {
                Notification::pushToUser(
                    userId: $doctorUserId,
                    centerId: (int) $centerId,
                    title: 'Appointment cancelled',
                    message: "Appointment on {$dt->toDateString()} at {$dt->format('H:i')} has been cancelled by the center."
                );
            }

            // Broadcast لمرضى عندن Approved بعد الوقت الملغى بنفس اليوم
            $dateStr = $dt->toDateString();
            $timeStr = $dt->format('H:i:s');

            $patientIds = AppointmentRequest::where('doctor_id', $doctorId)
                ->where('center_id', $centerId)
                ->whereDate('requested_date', $dateStr)
                ->whereTime('requested_date', '>', $timeStr)
                ->where('status', 'approved')
                ->pluck('patient_id')
                ->filter(fn ($pid) => (int) $pid !== (int) $appointmentRequest->patient_id) // استثناء صاحب الإلغاء
                ->unique()
                ->values()
                ->all();

            if (!empty($patientIds)) {
                Notification::pushToUsers(
                    userIds: $patientIds,
                    centerId: (int) $centerId,
                    title: 'New slot available',
                    message: "A slot opened today at {$dt->format('H:i')} with Dr. {$doctorName}. You may reschedule if you want."
                );
            }
        }

        return $this->unifiedResponse(true, 'Appointment request deleted (marked as rejected) successfully.');
    }



    // public function deleteAppointmentRequest($id)
    // {
    //     $centerId = auth()->user()->secretaries->first()->center_id;

    //     $appointmentRequest = AppointmentRequest::where('id', $id)
    //         ->where('center_id', $centerId)
    //         ->whereIn('status', ['pending', 'approved'])
    //         ->first();

    //     if (!$appointmentRequest) {
    //         return $this->unifiedResponse(false, 'Appointment request not found or already processed.', [], [], 404);
    //     }

    //     $appointmentRequest->update(['status' => 'rejected']);

    //     // إشعار للمريض
    //     if ($appointmentRequest->patient_id) {
    //         Notification::pushToUser(
    //             userId: (int) $appointmentRequest->patient_id,
    //             centerId: (int) $centerId,
    //             title: 'Booking cancelled',
    //             message: 'Your appointment request has been cancelled. Please select another time.'
    //         );
    //     }

    //     return $this->unifiedResponse(true, 'Appointment request deleted (marked as rejected) successfully.');
    // }

// public function deleteAppointmentRequest($id)
// {
//     $centerId = auth()->user()->secretaries->first()->center_id;

//     $appointmentRequest = AppointmentRequest::where('id', $id)
//         ->where('center_id', $centerId)
//         ->whereIn('status', ['pending', 'approved'])
//         ->first();

//     if (!$appointmentRequest) {
//         return $this->unifiedResponse(false, 'Appointment request not found or already processed.', [], [], 404);
//     }

//     $appointmentRequest->update(['status' => 'rejected']);

//     return $this->unifiedResponse(true, 'Appointment request deleted (marked as rejected) successfully.');
// }


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

        $dt = $appointmentRequest->requested_date instanceof \Carbon\Carbon
            ? $appointmentRequest->requested_date
            : \Carbon\Carbon::parse($appointmentRequest->requested_date);

        $appointmentDate = $dt->toDateString();
        $appointmentTime = $dt->format('H:i:s');

        $appointment = Appointment::create([
            'doctor_id'         => $appointmentRequest->doctor_id,
            'patient_id'        => $appointmentRequest->patient_id,
            'appointment_date'  => $appointmentDate,
            'appointment_time'  => $appointmentTime,
            'status'            => 'approved',
            'booked_by'         => auth()->id(),
            'attendance_status' => $status,
            'notes'             => $appointmentRequest->notes,
        ]);

        $appointmentRequest->delete();

        // ===== إشعارات =====
        // للمريض
        Notification::pushToUser(
            userId: (int) $appointment->patient_id,
            centerId: (int) $centerId,
            title: 'Appointment created',
            message: "Your appointment on {$appointmentDate} at {$appointmentTime} has been created."
        );

        // للدكتور
        $doctorUserId = (int) \DB::table('doctors')->where('id', $appointment->doctor_id)->value('user_id');
        if ($doctorUserId) {
            Notification::pushToUser(
                userId: $doctorUserId,
                centerId: (int) $centerId,
                title: 'New appointment',
                message: "You have a new appointment on {$appointmentDate} at {$appointmentTime}."
            );
        }

        return $this->unifiedResponse(true, 'Attendance confirmed and appointment created successfully.', $appointment);
    }


    // public function confirmAttendance($id, $status)
    // {
    //     $allowed = ['present', 'absent'];
    //     if (!in_array($status, $allowed)) {
    //         return $this->unifiedResponse(false, 'Invalid attendance status. Allowed values: present, absent.', [], [], 422);
    //     }

    //     $centerId = auth()->user()->secretaries->first()->center_id;

    //     $appointmentRequest = AppointmentRequest::where('id', $id)
    //         ->where('center_id', $centerId)
    //         ->where('status', 'approved')
    //         ->first();

    //     if (!$appointmentRequest) {
    //         return $this->unifiedResponse(false, 'Appointment request not found or not approved.', [], [], 404);
    //     }

    //     $dt = $appointmentRequest->requested_date instanceof \Carbon\Carbon
    //         ? $appointmentRequest->requested_date
    //         : \Carbon\Carbon::parse($appointmentRequest->requested_date);

    //     $appointmentDate = $dt->toDateString();
    //     $appointmentTime = $dt->format('H:i:s');

    //     $appointment = Appointment::create([
    //         'doctor_id'         => $appointmentRequest->doctor_id,
    //         'patient_id'        => $appointmentRequest->patient_id,
    //         // 'appointment_date'  => $appointmentRequest->requested_date->format('Y-m-d'),
    //         'appointment_date'  => $appointmentDate,
    //         'appointment_time'  => $appointmentTime,
    //         'status'            => 'approved',
    //         'booked_by'         => auth()->id(),
    //         'attendance_status' => $status,
    //         'notes'             => $appointmentRequest->notes,
    //     ]);

    //     $appointmentRequest->delete();

    //     return $this->unifiedResponse(true, 'Attendance confirmed and appointment created successfully.', $appointment);
    // }




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
