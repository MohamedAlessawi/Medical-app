<?php

namespace App\Services\SuperAdmin;

use App\Models\User;
use App\Models\Role;
use App\Traits\ApiResponseTrait;

class CenterAdminService
{
    use ApiResponseTrait;

    // public function listCenterAdmins()
    // {
    //     $admins = User::whereHas('roles', function($q) {
    //         $q->where('name', 'admin');
    //     })->get();

    //     return $this->unifiedResponse(true, 'Center admins retrieved successfully.', $admins);
    // }

    public function listCenterAdmins()
    {
        $admins = \App\Models\User::whereHas('roles', fn($q) => $q->where('name','admin'))
            ->with(['adminCenters.center:id,name'])
            ->orderBy('full_name')
            ->get()
            ->map(function ($u) {
                $centers = $u->adminCenters->map(fn($ac) => [
                    'center_id'   => $ac->center->id,
                    'center_name' => $ac->center->name,
                ])->values();

                $first = $centers->first();

                return [
                    'id'          => $u->id,
                    'full_name'   => $u->full_name,
                    'email'       => $u->email,
                    'phone'       => $u->phone,
                    'center_id'   => $first['center_id']   ?? null,
                    'center_name' => $first['center_name'] ?? null,
                    // 'centers'     => $centers,
                    'is_active'   => (bool) $u->is_active,
                ];
            });

        return $this->unifiedResponse(true, 'Center admins retrieved successfully.', $admins);
    }

    // public function getCenterAdminById($id)
    // {
    //     $admin = User::with('adminCenters.center')->find($id);
    //     if (!$admin) {
    //         return $this->unifiedResponse(false, 'Admin not found.', [], [], 404);
    //     }

    //     return $this->unifiedResponse(true, 'Admin retrieved successfully.', $admin);
    // }

    public function getCenterAdminById($id)
    {
        $admin = \App\Models\User::with(['adminCenters.center:id,name'])->find($id);
        if (!$admin) {
            return $this->unifiedResponse(false, 'Admin not found.', [], [], 404);
        }

        $centers = $admin->adminCenters->map(fn($ac) => [
            'center_id'   => $ac->center->id,
            'center_name' => $ac->center->name,
        ])->values();

        $first = $centers->first();

        $data = [
            'id'          => $admin->id,
            'full_name'   => $admin->full_name,
            'email'       => $admin->email,
            'phone'       => $admin->phone,
            'center_id'   => $first['center_id']   ?? null,
            'center_name' => $first['center_name'] ?? null,
            // 'centers'     => $centers,
            'is_active'   => (bool) $admin->is_active,
        ];

        return $this->unifiedResponse(true, 'Admin retrieved successfully.', $data);
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
