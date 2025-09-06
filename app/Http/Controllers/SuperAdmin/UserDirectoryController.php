<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Services\SuperAdmin\UserDirectoryService;

class UserDirectoryController extends Controller
{
    public function __construct(private UserDirectoryService $service) {}

    public function index()
    {
        return $this->service->listAllUsersWithRoles();
    }
}
