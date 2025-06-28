<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\Role;
use App\Models\UserRole;

class UserRepository
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function create(array $data)
    {
        return User::create($data);
    }

    public function findByEmailOrPhone($credential)
    {
        return User::where('email', $credential)->orWhere('phone', $credential)->first();
    }

    public function attachRole($userId, $roleName)
    {
        $role = Role::where('name', $roleName)->first();
        if ($role) {
            UserRole::updateOrCreate(['user_id' => $userId], ['role_id' => $role->id]);
        }
        return $role;
    }

    public function getUserRoles($userId)
    {
        return User::with('roles')->find($userId)->roles->pluck('name')->first();
    }
}
