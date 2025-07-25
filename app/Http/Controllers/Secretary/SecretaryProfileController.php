<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Services\Secretary\SecretaryService;
use Illuminate\Http\Request;

class SecretaryProfileController extends Controller
{
    protected $secretaryService;

    public function __construct(SecretaryService $secretaryService)
    {
        $this->secretaryService = $secretaryService;
    }


    public function getProfile(Request $request)
    {
        $userId = $request->user()->id;
        return $this->secretaryService->getProfile($userId);
    }


    public function updateProfile(Request $request)
    {
        $userId = $request->user()->id;
        $data = $request->only(['full_name', 'email', 'phone', 'address']);
        return $this->secretaryService->updateProfile($userId, $data);
    }

    
    public function updateProfilePhoto(Request $request)
    {
        $userId = $request->user()->id;
        return $this->secretaryService->updateProfilePhoto($request, $userId);
    }
}
