<?php

namespace App\Services\Admin;

use App\Traits\ApiResponseTrait;
use App\Repositories\Admin\AdminReportRepository;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class AdminReportService
{
    use ApiResponseTrait;

    protected $repo;

    public function __construct(AdminReportRepository $repo)
    {
        $this->repo = $repo;
    }

    public function appointmentsTrend(?string $from, ?string $to)
    {
        $userId   = Auth::id();
        $centerId = $this->repo->resolveCenterIdForUser($userId);
        if (!$centerId) return $this->unifiedResponse(true, 'No center linked for this user.', []);

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(6)->startOfDay();
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : now()->endOfDay();

        $data = $this->repo->appointmentsTrend($centerId, $fromDate, $toDate);
        return $this->unifiedResponse(true, 'Appointments trend fetched.', $data);
    }

    public function topDoctors(?string $from, ?string $to, int $limit = 10)
    {
        $userId   = Auth::id();
        $centerId = $this->repo->resolveCenterIdForUser($userId);
        if (!$centerId) return $this->unifiedResponse(true, 'No center linked for this user.', []);

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(29)->startOfDay();
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : now()->endOfDay();

        $rows = $this->repo->topDoctorsByCompleted($centerId, $fromDate, $toDate, $limit);
        return $this->unifiedResponse(true, 'Top doctors fetched.', $rows);
    }

    public function newPatients(?string $from, ?string $to)
    {
        $userId   = Auth::id();
        $centerId = $this->repo->resolveCenterIdForUser($userId);
        if (!$centerId) return $this->unifiedResponse(true, 'No center linked for this user.', []);

        $fromDate = $from ? Carbon::parse($from)->startOfDay() : now()->subDays(6)->startOfDay();
        $toDate   = $to   ? Carbon::parse($to)->endOfDay()   : now()->endOfDay();

        $rows = $this->repo->newPatientsByDay($centerId, $fromDate, $toDate);
        return $this->unifiedResponse(true, 'New patients by day fetched.', $rows);
    }

    public function centerDetailedReport(?string $from, ?string $to)
    {
        $userId   = \Auth::id();
        $centerId = $this->repo->resolveCenterIdForUser($userId);
        if (!$centerId) {
            return $this->unifiedResponse(true, 'No center linked for this user.', []);
        }

        $fromDate = $from ? \Carbon\Carbon::parse($from)->startOfDay() : now()->startOfMonth();
        $toDate   = $to   ? \Carbon\Carbon::parse($to)->endOfDay()   : now()->endOfDay();

        $data = $this->repo->detailedCenterReport($centerId, $fromDate, $toDate);

        return $this->unifiedResponse(true, 'Center detailed report fetched.', $data);
    }

}
