<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\CenterAdminService;
use Illuminate\Http\Request;

class CenterAdminController extends Controller
{
    protected $centerAdminService;

    public function __construct(CenterAdminService $centerAdminService)
    {
        $this->centerAdminService = $centerAdminService;
    }

    public function index()
    {
        return $this->centerAdminService->listCenterAdmins();
    }

    public function show($id)
    {
        return $this->centerAdminService->getCenterAdminById($id);
    }

    public function update(Request $request, $id)
    {
        return $this->centerAdminService->updateCenterAdmin($id, $request->all());
    }

    public function toggleStatus($id)
    {
        return $this->centerAdminService->toggleCenterAdminStatus($id);
    }
}
