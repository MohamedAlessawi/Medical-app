<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Services\Secretary\SecretaryReportService;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    protected $service;

    public function __construct(SecretaryReportService $service)
    {
        $this->service = $service;
    }

    public function detailed(Request $request)
    {
        $scope = $request->query('scope', 'today'); // today | week
        $from  = $request->query('from');
        $to    = $request->query('to');

        return $this->service->detailed($scope, $from, $to);
    }
}
