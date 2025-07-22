<?php

namespace App\Services\SuperAdmin;

use App\Models\User;
use App\Models\Role;
use App\Traits\ApiResponseTrait;

class CenterAdminService
{
    use ApiResponseTrait;

    public function listCenterAdmins()
    {
        $admins = User::whereHas('roles', function($q) {
            $q->where('name', 'admin');
        })->get();

        return $this->unifiedResponse(true, 'Center admins retrieved successfully.', $admins);
    }

    public function getCenterAdminById($id)
    {
        $admin = User::with('adminCenters.center')->find($id);
        if (!$admin) {
            return $this->unifiedResponse(false, 'Admin not found.', [], [], 404);
        }

        return $this->unifiedResponse(true, 'Admin retrieved successfully.', $admin);
    }

    public function updateCenterAdmin($id, $data)
    {
        $admin = User::find($id);
        if (!$admin) {
            return $this->unifiedResponse(false, 'Admin not found.', [], [], 404);
        }

        $admin->update($data);
        return $this->unifiedResponse(true, 'Admin updated successfully.', $admin);
    }

    public function toggleCenterAdminStatus($id)
    {
        $admin = User::find($id);
        if (!$admin) {
            return $this->unifiedResponse(false, 'Admin not found.', [], [], 404);
        }

        $admin->is_active = !$admin->is_active;
        $admin->save();

        return $this->unifiedResponse(true, 'Admin status toggled.', $admin);
    }
}
