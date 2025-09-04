<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardService;

class DashboardController extends Controller
{
    protected $service;

    public function __construct(AdminDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return $this->service->overview();
    }
}
