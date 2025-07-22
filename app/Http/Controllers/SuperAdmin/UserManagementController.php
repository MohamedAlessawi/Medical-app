<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\UserManagementService;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    protected $userService;

    public function __construct(UserManagementService $userService)
    {
        $this->userService = $userService;
    }

    public function toggleStatus($id)
    {
        return $this->userService->toggleUserStatus($id);
    }

    public function assignRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name'
        ]);

        return $this->userService->assignRoleToUser($id, $request->role);
    }
}
