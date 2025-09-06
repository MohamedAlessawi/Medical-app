<?php

namespace App\Services\SuperAdmin;

use App\Traits\ApiResponseTrait;

class UserDirectoryService
{
    use ApiResponseTrait;

    public function listAllUsersWithRoles()
    {
        $users = \App\Models\User::with('roles:id,name')
            ->orderBy('full_name')
            ->get()
            ->map(function ($u) {
                return [
                    'id'        => $u->id,
                    'full_name' => $u->full_name,
                    'email'     => $u->email,
                    'phone'     => $u->phone,
                    'roles'     => $u->roles->pluck('name')->values(),
                    'is_active' => (bool) $u->is_active,
                ];
            });

        return $this->unifiedResponse(true, 'All users with roles.', $users);
    }
}
