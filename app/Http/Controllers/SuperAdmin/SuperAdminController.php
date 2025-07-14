<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterCenterAdminRequest;
use App\Services\SuperAdmin\CenterAdminRegistrationService;


class SuperAdminController extends Controller
{

    public function __construct(CenterAdminRegistrationService $centerAdminRegistrationService)
    {
        $this->centerAdminRegistrationService = $centerAdminRegistrationService;
    }

   public function registerCenterAdmin(RegisterCenterAdminRequest $request)
    {
        $result = $this->centerAdminRegistrationService->registerCenterWithAdmin($request);
        return $result;
    }

}
