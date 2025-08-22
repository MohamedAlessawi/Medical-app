<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InviteDoctorRequest;
use App\Http\Requests\Admin\ToggleStatusRequest;
use App\Services\Admin\DoctorService;
use Illuminate\Http\Request;

class DoctorManagementController extends Controller
{
    public function __construct(private DoctorService $service) {}

    public function index()
    {
        return $this->service->list();
    }

    public function invite(InviteDoctorRequest $r)
    {
        return $this->service->invite($r->validated());
    }

    public function toggle($doctorId, ToggleStatusRequest $r)
    {
        return $this->service->toggleStatus((int)$doctorId, (bool)$r->is_active);
    }

    public function remove($doctorId)
    {
        return $this->service->remove((int)$doctorId);
    }

    // public function candidates()
    // {
    //     return $this->service->candidates();
    // }


    public function candidates(Request $request)
    {
        return $this->service->candidates($request->query('search'));
    }
}
