<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Doctor\DoctorProfileService;

class DoctorProfileController extends Controller
{
    protected $doctorProfileService;

    public function __construct(DoctorProfileService $doctorProfileService)
    {
        $this->doctorProfileService = $doctorProfileService;
    }

    public function storeOrUpdate(Request $request)
    {
        return $this->doctorProfileService->storeOrUpdate($request);
    }

    public function show()
    {
        return $this->doctorProfileService->show();
    }
}
