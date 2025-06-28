<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\AdminUserService;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    protected $adminUserService;

    public function __construct(AdminUserService $adminUserService)
    {
        $this->adminUserService = $adminUserService;
    }

    public function addUserRole(Request $request)
    {
        $result = $this->adminUserService->addUserRole($request);
        return $result;
    }
}
