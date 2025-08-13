<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ToggleStatusRequest;
use App\Services\Admin\SecretaryService;

class SecretaryManagementController extends Controller
{
    public function __construct(private SecretaryService $service) {}

    public function index()
    {
        return $this->service->index();
    }

    public function toggle($userId, ToggleStatusRequest $r)
    {
        return $this->service->toggle((int)$userId, (bool)$r->is_active);
    }

    public function remove($userId)
    {
        return $this->service->remove((int)$userId);
    }
}
