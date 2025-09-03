<?php

namespace App\Services\Admin;

use App\Traits\ApiResponseTrait;
use App\Repositories\Admin\AdminDashboardRepository;
use Illuminate\Support\Facades\Auth;

class AdminDashboardService
{
    use ApiResponseTrait;

    protected $repo;

    public function __construct(AdminDashboardRepository $repo)
    {
        $this->repo = $repo;
    }

    public function overview()
    {
        $userId   = Auth::id();
        $centerId = $this->repo->resolveCenterIdForUser($userId);

        if (!$centerId) {
            return $this->unifiedResponse(true, 'No center linked for this user.', [
                'cards' => [
                    'total_appointments' => 0,
                    'active_secretaries' => 0,
                    'total_doctors'      => 0,
                    'total_patients'     => 0,
                ],
                'center_status' => [
                    'occupancy_rate'     => 0,
                    'patient_satisfaction'=> 0,
                    'general_performance'=> 0,
                ],
                'today_summary' => [
                    'new_appointments'   => 0,
                    'completed_today'    => 0,
                    'pending_today'      => 0,
                ],
                'chart_last7' => [],
            ]);
        }

        // Cards
        $completedAll      = $this->repo->countCompletedAppointments($centerId); // from appointments
        $pendingAll        = $this->repo->countAppointmentRequestsByStatus($centerId, 'pending');   // from requests
        $approvedAll       = $this->repo->countAppointmentRequestsByStatus($centerId, 'approved');  // from requests
        $rejectedAll       = $this->repo->countAppointmentRequestsByStatus($centerId, 'rejected');  // from requests
        $totalAppointments = $completedAll + $pendingAll + $approvedAll + $rejectedAll;

        $activeSecretaries = $this->repo->countActiveSecretaries($centerId);
        $totalDoctors      = $this->repo->countDoctors($centerId);
        $totalPatients     = $this->repo->countPatients($centerId);

        // Today summary
        $today = now();
        $newToday      = $this->repo->countAppointmentRequestsByStatus($centerId, null, $today, 'created_at'); // جديد اليوم
        $completedToday= $this->repo->countCompletedAppointments($centerId, $today); // مكتمل اليوم
        $pendingToday  = $this->repo->countAppointmentRequestsByStatus($centerId, 'pending', $today, 'requested_date'); // المعلّق بتاريخ اليوم

        // Center Status
        $requestsToday = $this->repo->countAppointmentRequestsByStatus($centerId, null, $today, 'created_at');
        $occupancyRate = ($completedToday + $this->repo->countAppointmentRequestsByStatus($centerId, 'approved', $today, 'requested_date'));
        $denominator   = max(1, $requestsToday); // proxy بسيط بلا working_hours
        $occupancyRate = (int) round(($occupancyRate / $denominator) * 100);

        $avgRating     = $this->repo->avgCenterRating($centerId, 30); // 0..5
        $satisfaction  = $avgRating ? (int) round(($avgRating / 5) * 100) : 0;

        $generalPerf   = (int) round(($occupancyRate + $satisfaction) / 2);

        // Chart
        $chartLast7 = $this->repo->appointmentsTrendLastNDays($centerId, 7);

        $data = [
            'cards' => [
                'total_appointments' => $totalAppointments,
                'active_secretaries' => $activeSecretaries,
                'total_doctors'      => $totalDoctors,
                'total_patients'     => $totalPatients,
            ],
            'center_status' => [
                'occupancy_rate'      => $occupancyRate,        // 0..100
                'patient_satisfaction' => $satisfaction,        // 0..100
                'general_performance'  => $generalPerf,         // 0..100
            ],
            'today_summary' => [
                'new_appointments'     => $newToday,
                'completed_today'      => $completedToday,
                'pending_today'        => $pendingToday,
            ],
            'chart_last7' => $chartLast7,
        ];

        return $this->unifiedResponse(true, 'Dashboard overview fetched.', $data);
    }
}
