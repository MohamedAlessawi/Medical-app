<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Traits\ApiResponseTrait;

class ReportController extends Controller
{
    use ApiResponseTrait;

    public function index()
    {
        $reports = Report::with('user')->latest()->get();
        return $this->unifiedResponse(true, 'Reports retrieved.', $reports);
    }

    public function show($id)
    {
        $report = Report::with('user')->find($id);
        if (!$report) {
            return $this->unifiedResponse(false, 'Report not found.', [], [], 404);
        }

        return $this->unifiedResponse(true, 'Report retrieved.', $report);
    }
}
