<?php

namespace App\Services\SuperAdmin;

use App\Models\Center;
use App\Traits\ApiResponseTrait;

class CenterService
{
    use ApiResponseTrait;

    public function listCenters()
    {
        $centers = Center::withCount(['adminCenters', 'secretaries', 'doctors'])->get();
        return $this->unifiedResponse(true, 'Centers retrieved successfully.', $centers);
    }

    public function getCenterById($id)
    {
        $center = Center::with(['adminCenters.user', 'secretaries.user', 'doctors'])->find($id);
        if (!$center) {
            return $this->unifiedResponse(false, 'Center not found.', [], [], 404);
        }
        return $this->unifiedResponse(true, 'Center retrieved successfully.', $center);
    }

    public function updateCenter($id, $data)
    {
        $center = Center::find($id);
        if (!$center) {
            return $this->unifiedResponse(false, 'Center not found.', [], [], 404);
        }
        $center->update($data);
        return $this->unifiedResponse(true, 'Center updated successfully.', $center);
    }

    public function toggleCenterStatus($id)
    {
        $center = Center::find($id);
        if (!$center) {
            return $this->unifiedResponse(false, 'Center not found.', [], [], 404);
        }
        $center->is_active = !$center->is_active;
        $center->save();
        return $this->unifiedResponse(true, 'Center status toggled.', $center);
    }
}
