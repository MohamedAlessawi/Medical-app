<?php

namespace App\Services\SuperAdmin;

use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;
use App\Traits\ApiResponseTrait;

class UserManagementService
{
    use ApiResponseTrait;

    public function toggleUserStatus($id)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->unifiedResponse(false, 'User not found.', [], [], 404);
        }

        $user->is_active = !$user->is_active;
        $user->save();

        return $this->unifiedResponse(true, 'User status toggled.', $user);
    }

    public function assignRoleToUser($userId, $roleName)
    {
        $user = User::find($userId);
        $role = Role::where('name', $roleName)->first();

        if (!$user || !$role) {
            return $this->unifiedResponse(false, 'User or role not found.', [], [], 404);
        }

        UserRole::firstOrCreate([
            'user_id' => $user->id,
            'role_id' => $role->id
        ]);

        return $this->unifiedResponse(true, 'Role assigned to user.', [
            'user_id' => $user->id,
            'role' => $role->name
        ]);
    }
}
