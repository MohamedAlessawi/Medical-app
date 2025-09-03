<?php

namespace App\Repositories\Admin;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AdminReportRepository
{
    public function resolveCenterIdForUser(int $userId): ?int
    {
        $centerId = DB::table('admin_centers')->where('user_id', $userId)->value('center_id');
        if ($centerId) return $centerId;
        return DB::table('secretaries')->where('user_id', $userId)->value('center_id');
    }

    /** ترند المواعيد ضمن فترة */
    public function appointmentsTrend(int $centerId, Carbon $from, Carbon $to): array
    {
        $cursor = $from->copy();
        $data = [];

        while ($cursor->lte($to)) {
            $day = $cursor->copy();

            $newRequests = DB::table('appointment_requests')
                ->where('center_id', $centerId)
                ->whereDate('created_at', $day->toDateString())
                ->count();

            $completed = DB::table('appointments as a')
                ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
                ->where('d.center_id', $centerId)
                ->where('a.status', 'completed')
                ->whereDate('a.appointment_date', $day->toDateString())
                ->count();

            $data[] = [
                'date'         => $day->toDateString(),
                'new_requests' => (int) $newRequests,
                'completed'    => (int) $completed,
            ];

            $cursor->addDay();
        }

        return $data;
    }

    /** Top الأطباء بعدد المواعيد المكتملة ضمن فترة */
    public function topDoctorsByCompleted(int $centerId, Carbon $from, Carbon $to, int $limit = 10): array
    {
        return DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->join('users as u', 'd.user_id', '=', 'u.id')
            ->where('d.center_id', $centerId)
            ->where('a.status', 'completed')
            ->whereBetween('a.appointment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('a.doctor_id', 'u.full_name', 'd.id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->get([
                'd.id as doctor_id',
                'u.full_name',
                DB::raw('COUNT(*) as completed_count'),
            ])->map(fn($r) => (array) $r)->toArray();
    }

    /** المرضى الجدد (انضمّوا للمركز) */
    public function newPatientsByDay(int $centerId, Carbon $from, Carbon $to): array
    {
        $rows = DB::table('user_centers')
            ->selectRaw('DATE(created_at) as day, COUNT(DISTINCT user_id) as cnt')
            ->where('center_id', $centerId)
            ->whereBetween('created_at', [$from->startOfDay(), $to->endOfDay()])
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $map = $rows->keyBy('day');
        $cursor = $from->copy();
        $out = [];

        while ($cursor->lte($to)) {
            $day = $cursor->toDateString();
            $out[] = [
                'date'  => $day,
                'count' => (int) ($map[$day]->cnt ?? 0),
            ];
            $cursor->addDay();
        }

        return $out;
    }

    public function detailedCenterReport(int $centerId, \Carbon\Carbon $from, \Carbon\Carbon $to): array
    {
        // 1) المواعيد المكتملة (من appointments) عبر doctors.center_id
        $completedCount = \DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->where('d.center_id', $centerId)
            ->where('a.status', 'completed')
            ->whereBetween('a.appointment_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        // 2) بقية الحالات (من appointment_requests) ضمن الفترة
        // نعتمد requested_date للفترة؛ إذا بدك على created_at، غيّر الحقل تحت
        $dateField = 'requested_date';

        $pendingCount = \DB::table('appointment_requests')
            ->where('center_id', $centerId)
            ->where('status', 'pending')
            ->whereBetween($dateField, [$from->toDateString(), $to->toDateString()])
            ->count();

        $approvedCount = \DB::table('appointment_requests')
            ->where('center_id', $centerId)
            ->where('status', 'approved')
            ->whereBetween($dateField, [$from->toDateString(), $to->toDateString()])
            ->count();

        // الملغي: منطق مرن (rejected أو canceled إن وجدت)
        $canceledCount = \DB::table('appointment_requests')
            ->where('center_id', $centerId)
            ->whereIn('status', ['rejected', 'canceled'])
            ->whereBetween($dateField, [$from->toDateString(), $to->toDateString()])
            ->count();

        // إجمالي المواعيد بالفترة وفق تعريفك (المكتمل من appointments + باقي الحالات من requests)
        $totalAppointments = $completedCount + $pendingCount + $approvedCount + $canceledCount;

        // 3) إحصائيات الأطباء (distinct patients لكل دكتور) ضمن الفترة من appointments
        $doctorStats = \DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->join('users as u', 'd.user_id', '=', 'u.id')
            ->where('d.center_id', $centerId)
            ->whereBetween('a.appointment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('a.doctor_id', 'u.full_name')
            ->get([
                'a.doctor_id',
                'u.full_name as doctor_name',
                \DB::raw('COUNT(DISTINCT a.patient_id) as patients_count'),
            ])->map(fn($r) => (array) $r)->toArray();

        // 4) إحصائيات السكرتاريا (عدد المواعيد التي نسقّتها) من appointments حسب booked_by
        $secretaryStats = \DB::table('appointments as a')
            ->join('secretaries as s', function($j) {
                $j->on('a.booked_by', '=', 's.user_id');
            })
            ->join('users as u', 's.user_id', '=', 'u.id')
            ->where('s.center_id', $centerId)
            ->whereBetween('a.appointment_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('s.user_id', 'u.full_name')
            ->get([
                's.user_id as secretary_id',
                'u.full_name as secretary_name',
                \DB::raw('COUNT(*) as appointments_coordinated'),
            ])->map(fn($r) => (array) $r)->toArray();

        // 5) KPI: نسبة الحضور من appointments فقط = completed / جميع المواعيد من appointments بالفترة
        $appointmentsAll = \DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->where('d.center_id', $centerId)
            ->whereBetween('a.appointment_date', [$from->toDateString(), $to->toDateString()])
            ->count();

        $attendanceRate = $appointmentsAll > 0
            ? (int) round(($completedCount / $appointmentsAll) * 100)
            : 0;

        return [
            'period' => [
                'from' => $from->toDateString(),
                'to'   => $to->toDateString(),
            ],
            'summary' => [
                'total'     => (int) $totalAppointments,
                'completed' => (int) $completedCount,  // from appointments
                'pending'   => (int) $pendingCount,    // from appointment_requests
                'approved'  => (int) $approvedCount,   // from appointment_requests
                'canceled'  => (int) $canceledCount,   // from appointment_requests (rejected/canceled)
            ],
            'doctor_stats' => $doctorStats,           // [{doctor_id, doctor_name, patients_count}]
            'secretary_stats' => $secretaryStats,     // [{secretary_id, secretary_name, appointments_coordinated}]
            'kpi' => [
                'attendance_rate' => $attendanceRate  // 0..100
            ],
        ];
    }

}
