<?php

namespace App\Services\Secretary;

use App\Traits\ApiResponseTrait;
use App\Repositories\Secretary\SecretaryReportRepository;
use Illuminate\Support\Facades\Auth;

class SecretaryReportService
{
    use ApiResponseTrait;

    protected $repo;

    public function __construct(SecretaryReportRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * تقرير السكرتيرة التفصيلي (appointments فقط)
     * scope: today | week (افتراضي today)
     * from/to: فترة مخصصة (إن وُجدت تتجاوز scope)
     */
    public function detailed(?string $scope = 'today', ?string $from = null, ?string $to = null)
    {
        $userId   = Auth::id();
        $centerId = $this->repo->resolveCenterIdForSecretary($userId);

        if (!$centerId) {
            return $this->unifiedResponse(true, 'No center linked for this secretary.', []);
        }

        [$fromDate, $toDate] = $this->repo->resolveRange($from, $to, $scope ?? 'today');

        $schedule     = $this->repo->appointmentsSchedule($centerId, $fromDate, $toDate);
        $newPatients  = $this->repo->newPatients($centerId, $fromDate, $toDate);
        $summary      = $this->repo->summaryCounters($centerId, $fromDate, $toDate);

        $data = [
            'period' => [
                'from'  => $fromDate->toDateTimeString(),
                'to'    => $toDate->toDateTimeString(),
                'scope' => $from || $to ? 'custom' : ($scope ?? 'today'),
            ],
            'schedule'      => $schedule,     // جدول المواعيد
            'new_patients'  => $newPatients,  // المرضى الجدد
            'summary'       => $summary,      // إجمالي المواعيد + حضر/غاب + معدل الحضور + إجمالي المرضى
        ];

        return $this->unifiedResponse(true, 'Secretary detailed report fetched.', $data);
    }
}
