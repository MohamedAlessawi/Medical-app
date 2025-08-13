<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AddCenterServiceRequest;
use App\Services\Admin\ServiceManagementService;

class ServiceManagementController extends Controller
{
    public function __construct(private ServiceManagementService $service) {}

    public function catalog()
    {
        return $this->service->catalog();
    }

    public function index()
    {
        return $this->service->centerServices();
    }

    public function store(AddCenterServiceRequest $r)
    {
        return $this->service->add($r->validated());
    }
    
    public function destroy($id)
    {
        return $this->service->remove((int)$id);
    }
}
