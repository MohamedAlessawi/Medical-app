<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\DoctorApprovalService;

class DoctorApprovalController extends Controller
{
    protected $approvalService;

    public function __construct(DoctorApprovalService $approvalService)
    {
        $this->approvalService = $approvalService;
    }

    public function listPending()
    {
        return $this->approvalService->listPending();
    }

    public function approve($id)
    {
        return $this->approvalService->approve($id);
    }

    public function reject($id)
    {
        return $this->approvalService->reject($id);
    }

    public function listAll()
    {
        return $this->approvalService->listAllDoctors();
    }
}
