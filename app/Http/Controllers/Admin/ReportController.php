<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $service;

    public function __construct(AdminReportService $service)
    {
        $this->service = $service;
    }

    public function appointmentsTrend(Request $request)
    {
        return $this->service->appointmentsTrend($request->query('from'), $request->query('to'));
    }

    public function topDoctors(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        return $this->service->topDoctors($request->query('from'), $request->query('to'), $limit);
    }

    public function newPatients(Request $request)
    {
        return $this->service->newPatients($request->query('from'), $request->query('to'));
    }

    public function centerDetailed(Request $request)
    {
        return $this->service->centerDetailedReport($request->query('from'), $request->query('to'));
    }

}
