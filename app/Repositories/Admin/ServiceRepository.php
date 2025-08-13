<?php

namespace App\Repositories\Admin;

use App\Models\Service;
use App\Models\CenterService;

class ServiceRepository
{
    public function catalog()
    {
        return Service::where('is_active', true)->orderBy('name')->get();
    }

    public function centerServices(int $centerId)
    {
        return CenterService::with('service')->where('center_id', $centerId)->get();
    }

    public function addToCenter(int $centerId, int $serviceId): CenterService
    {
        $link = CenterService::where('center_id',$centerId)->where('service_id',$serviceId)->first();
        if ($link) { return $link; }
        return CenterService::create(['center_id'=>$centerId,'service_id'=>$serviceId]);
    }

    public function removeCenterService(int $id): void
    {
        CenterService::where('id', $id)->delete();
    }
}
