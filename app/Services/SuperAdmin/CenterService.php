<?php

namespace App\Services\SuperAdmin;

use App\Models\Center;
use App\Traits\ApiResponseTrait;

class CenterService
{
    use ApiResponseTrait;

    // public function listCenters()
    // {
    //     $centers = Center::withCount(['adminCenters', 'secretaries', 'doctors'])->get();
    //     return $this->unifiedResponse(true, 'Centers retrieved successfully.', $centers);
    // }

    public function listCenters()
    {
        $centers = \App\Models\Center::with(['adminCenters.user'])
            ->withCount(['adminCenters', 'secretaries', 'doctors'])
            ->get()
            ->map(function ($c) {
                $firstAdmin = $c->adminCenters->first()?->user;

                return [
                    'id'               => $c->id,
                    'name'             => $c->name,
                    'location'         => $c->location,
                    'phone'            => $c->phone,
                    'image'            => $c->image,
                    'is_active'        => (bool) $c->is_active,
                    'admin_user_id'    => $firstAdmin?->id,
                    'admin_full_name'  => $firstAdmin?->full_name,
                    // 'admins'           => $c->adminCenters->map(fn($ac) => [
                    //     'user_id'   => $ac->user->id,
                    //     'full_name' => $ac->user->full_name,
                    // ])->values(),
                    'counts' => [
                        'admins'      => $c->admin_centers_count,
                        'secretaries' => $c->secretaries_count,
                        'doctors'     => $c->doctors_count,
                    ],
                ];
            });

        return $this->unifiedResponse(true, 'Centers retrieved successfully.', $centers);
    }


    public function getCenterById($id)
    {
        $center = \App\Models\Center::with(['adminCenters.user', 'secretaries.user', 'doctors'])->find($id);
        if (!$center) {
            return $this->unifiedResponse(false, 'Center not found.', [], [], 404);
        }

        $firstAdmin = $center->adminCenters->first()?->user;

        $data = [
            'id'               => $center->id,
            'name'             => $center->name,
            'location'         => $center->location,
            'phone'            => $center->phone,
            'image'            => $center->image,
            'is_active'        => (bool) $center->is_active,
            'admin_user_id'    => $firstAdmin?->id,
            'admin_full_name'  => $firstAdmin?->full_name,
            // 'admins'           => $center->adminCenters->map(fn($ac) => [
            //     'user_id'   => $ac->user->id,
            //     'full_name' => $ac->user->full_name,
            // ])->values(),
        ];

        return $this->unifiedResponse(true, 'Center retrieved successfully.', $data);
    }

    // public function getCenterById($id)
    // {
    //     $center = Center::with(['adminCenters.user', 'secretaries.user', 'doctors'])->find($id);
    //     if (!$center) {
    //         return $this->unifiedResponse(false, 'Center not found.', [], [], 404);
    //     }
    //     return $this->unifiedResponse(true, 'Center retrieved successfully.', $center);
    // }

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
