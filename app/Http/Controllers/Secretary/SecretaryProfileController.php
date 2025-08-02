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

        $validated = $request->validate([
            'full_name' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $userId,
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
        ]);

        return $this->secretaryService->updateProfile($userId, $validated);
    }


    public function updateProfilePhoto(Request $request)
    {
        $userId = $request->user()->id;


        $request->validate([
            'photo' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048'
        ]);

        return $this->secretaryService->updateProfilePhoto($request, $userId);
    }
}
