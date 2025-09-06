<?php

namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminDashboardRepository
{
    /** يرجّع أول center_id للأدمن، أو للسكرتير كـ fallback */
    public function resolveCenterIdForUser(int $userId): ?int
    {
        $centerId = DB::table('admin_centers')->where('user_id', $userId)->value('center_id');
        if ($centerId) return $centerId;

        return DB::table('secretaries')->where('user_id', $userId)->value('center_id');
    }

    /** المواعيد المكتملة من appointments عبر join على doctors.center_id */
    public function countCompletedAppointments(int $centerId, ?Carbon $onDate = null): int
    {
        $q = DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->where('d.center_id', $centerId)
            ->where('a.attendance_status', 'present');

        if ($onDate) {
            $q->whereDate('a.appointment_date', $onDate->toDateString());
        }

        return (int) $q->count();
    }

    /** عدّاد من appointment_requests حسب الحالة */
    public function countAppointmentRequestsByStatus(int $centerId, ?string $status = null, ?Carbon $onDate = null, string $dateField = 'created_at'): int
    {
        $q = DB::table('appointment_requests')
            ->where('center_id', $centerId);

        if (!empty($status)) {
            $q->where('status', $status);
        }

        if ($onDate) {
            $q->whereDate($dateField, $onDate->toDateString());
        }

        return (int) $q->count();
    }

    public function countActiveSecretaries(int $centerId): int
    {
        // بافتراض وجود عمود status في secretaries (active/inactive)
        return (int) DB::table('secretaries')->where('center_id', $centerId)->where('is_active', true)->count();
    }

    public function countDoctors(int $centerId): int
    {
        // بافتراض وجود عمود status في doctors
        return (int) DB::table('doctors')->where('center_id', $centerId)->count();
    }

    public function countPatients(int $centerId): int
    {
        // نعدّ المرضى المنضمين لهذا المركز من user_centers (distinct)
        return (int) DB::table('user_centers')->where('center_id', $centerId)->distinct('user_id')->count('user_id');
    }

    public function avgCenterRating(int $centerId, int $days = 30): ?float
    {
        // // لو عندك ratings على المركز مباشرة:
        $q = DB::table('ratings')->where('rateable_id', $centerId);
        if ($days > 0) {
            $q->where('created_at', '>=', now()->subDays($days));
        }
        $avg = $q->avg('score');
        return $avg ? (float) $avg : null;
        // return 3.5 ;
    }

    /** ترند لآخر N أيام: requests الجديدة + completed */
    public function appointmentsTrendLastNDays(int $centerId, int $days = 7): array
    {
        $start = now()->copy()->subDays($days - 1)->startOfDay();
        $data = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);

            $newRequests   = $this->countAppointmentRequestsByStatus($centerId, null, $day, 'created_at');
            $completed     = $this->countCompletedAppointments($centerId, $day);

            $data[] = [
                'date'           => $day->toDateString(),
                'new_requests'   => $newRequests,
                'completed'      => $completed,
            ];
        }

        return $data;
    }
}
