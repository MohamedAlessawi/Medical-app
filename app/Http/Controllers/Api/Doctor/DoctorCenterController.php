<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Services\Doctor\DoctorCenterService;
use Illuminate\Http\Request;

class DoctorCenterController extends Controller
{
    protected $centerService;

    public function __construct(DoctorCenterService $centerService)
    {
        $this->centerService = $centerService;
    }

    public function index(Request $request)
    {
        return $this->centerService->getDoctorCenters();
    }
}
