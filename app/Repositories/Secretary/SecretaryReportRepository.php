<?php

namespace App\Repositories\Secretary;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SecretaryReportRepository
{
    /** يُعيد center_id الخاص بالسكرتير */
    public function resolveCenterIdForSecretary(int $userId): ?int
    {
        return DB::table('secretaries')->where('user_id', $userId)->value('center_id');
    }

    /** يحدد الفترة الزمنية: today | week | custom (from/to) */
    public function resolveRange(?string $from, ?string $to, string $scope = 'today'): array
    {
        if ($from || $to) {
            $fromDate = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfDay();
            $toDate   = $to   ? Carbon::parse($to)->endOfDay()     : Carbon::now()->endOfDay();
            return [$fromDate, $toDate];
        }

        if ($scope === 'week') {
            $end = Carbon::now()->startOfDay();
            $start   = $end
            ->copy()->subDays(6)->endOfDay(); // اليوم + 6 أيام
            return [$start, $end];
        }

        // default: اليوم فقط
        $start = Carbon::now()->startOfDay();
        $end   = Carbon::now()->endOfDay();
        return [$start, $end];
    }

    /** جدول المواعيد ضمن الفترة (appointments فقط) مربوط بالمركز عبر doctors.center_id */
    public function appointmentsSchedule(int $centerId, Carbon $from, Carbon $to): array
    {
        return DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->leftJoin('users as up', 'a.patient_id', '=', 'up.id') // اسم المريض
            ->leftJoin('users as ud', 'd.user_id', '=', 'ud.id')    // اسم الطبيب
            ->where('d.center_id', $centerId)
            ->whereBetween('a.appointment_date', [$from->toDateTimeString(), $to->toDateTimeString()])
            ->orderBy('a.appointment_date')
            ->get([
                'a.id as appointment_id',
                'a.appointment_date',
                'a.status',                 // إن وجد
                'a.attendance_status',      // present | absent | null
                DB::raw("COALESCE(up.full_name, 'Unknown') as patient_name"),
                DB::raw("COALESCE(ud.full_name, 'Unknown') as doctor_name"),
            ])
            ->map(function ($r) {
                $dt = Carbon::parse($r->appointment_date);
                return [
                    'appointment_id'   => (int) $r->appointment_id,
                    'date'             => $dt->toDateString(),
                    'time'             => $dt->format('H:i'),
                    'patient_name'     => $r->patient_name,
                    'doctor_name'      => $r->doctor_name,
                    'status'           => $r->status,
                    'attendance_status'=> $r->attendance_status,
                ];
            })
            ->toArray();
    }

    public function newPatients(int $centerId, Carbon $from, Carbon $to): array
    {
        // المريض يُعتبر "جديد" إذا كان أول موعد له ضمن هذا المركز واقع ضمن الفترة المحددة
        $rows = \DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->join('users as u', 'a.patient_id', '=', 'u.id')
            ->where('d.center_id', $centerId)
            ->groupBy('a.patient_id', 'u.id', 'u.full_name', 'u.phone', 'u.email')
            ->select([
                'u.id as user_id',
                'u.full_name',
                'u.phone',
                'u.email',
                \DB::raw('MIN(a.appointment_date) as joined_at') // أول زيارة
            ])
            // HAVING على أول موعد ضمن الفترة
            ->havingRaw('MIN(a.appointment_date) BETWEEN ? AND ?', [
                $from->startOfDay()->toDateTimeString(),
                $to->endOfDay()->toDateTimeString()
            ])
            ->orderBy(\DB::raw('MIN(a.appointment_date)')) // ترتيب بحسب أول زيارة
            ->get();

        return $rows->map(function ($r) {
            return [
                'user_id'   => (int) $r->user_id,
                'full_name' => $r->full_name,
                'phone'     => $r->phone,
                'email'     => $r->email,
                'joined_at' => \Carbon\Carbon::parse($r->joined_at)->toDateTimeString(),
            ];
        })->toArray();
    }


    /** قائمة المرضى الجدد ضمن الفترة (من user_centers) */
    // public function newPatients(int $centerId, Carbon $from, Carbon $to): array
    // {
    //     return DB::table('user_centers as uc')
    //         ->join('users as u', 'uc.user_id', '=', 'u.id')
    //         ->where('uc.center_id', $centerId)
    //         ->whereBetween('uc.created_at', [$from->startOfDay(), $to->endOfDay()])
    //         ->orderBy('uc.created_at', 'asc')
    //         ->get([
    //             'u.id as user_id',
    //             'u.full_name',
    //             'u.phone',
    //             'u.email',
    //             'uc.created_at as joined_at',
    //         ])
    //         ->map(function ($r) {
    //             return [
    //                 'user_id'   => (int) $r->user_id,
    //                 'full_name' => $r->full_name,
    //                 'phone'     => $r->phone,
    //                 'email'     => $r->email,
    //                 'joined_at' => Carbon::parse($r->joined_at)->toDateTimeString(),
    //             ];
    //         })
    //         ->toArray();
    // }

    /** إجمالي المرضى المنتسبين للمركز (بدون فلترة زمنية) */
    public function totalPatients(int $centerId): int
    {
        return (int) DB::table('user_centers')
            ->where('center_id', $centerId)
            ->distinct('user_id')
            ->count('user_id');
    }

    /** الإحصائية النهائية للفترة من appointments حصراً + إجمالي المرضى + معدل الحضور */
    public function summaryCounters(int $centerId, Carbon $from, Carbon $to): array
    {
        $base = DB::table('appointments as a')
            ->join('doctors as d', 'a.doctor_id', '=', 'd.id')
            ->where('d.center_id', $centerId)
            ->whereBetween('a.appointment_date', [$from->toDateTimeString(), $to->toDateTimeString()]);

        $total    = (int) (clone $base)->count(); // إجمالي المواعيد ضمن الفترة (completed ≡ total)
        $present  = (int) (clone $base)->where('a.attendance_status', 'present')->count();
        $absent   = (int) (clone $base)->where('a.attendance_status', 'absent')->count();

        $attendanceRate = $total > 0 ? (int) round(($present / $total) * 100) : 0;

        $patientsTotal = $this->totalPatients($centerId); // إجمالي المرضى المنتسبين للمركز

        return [
            'total_appointments' => $total,          // إجمالي المواعيد ضمن الفترة
            'attended'           => $present,        // حضروا (present)
            'absent'             => $absent,         // لم يحضروا (absent)
            'attendance_rate'    => $attendanceRate, // 0..100
            'total_patients'     => $patientsTotal,  // إجمالي المرضى بالمركز (بدون فلترة زمنية)
        ];
    }
}
