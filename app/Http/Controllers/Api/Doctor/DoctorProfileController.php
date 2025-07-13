<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Doctor\DoctorProfileService;

class DoctorProfileController extends Controller
{
    protected $service;

    public function __construct(DoctorProfileService $service)
    {
        $this->service = $service;
    }

    public function show(Request $request)
    {
        $userId = $request->user()->id;

        $profile = $this->service->getFullProfile($userId);

        return response()->json([
            'success' => true,
            'data' => $profile
        ]);
    }
    public function update(Request $request)
{
    $validated = $request->validate([
        'about_me' => 'nullable|string|max:2000',
        'specialty_id' => 'nullable|exists:specialties,id',
        'phone' => 'nullable|string|max:20',
        'profile_photo' => 'nullable|string|max:255', // مسار أو رابط الصورة
    ]);

    $userId = $request->user()->id;
    $profile = $this->service->updateProfile($userId, $validated);

    return response()->json([
        'success' => true,
        'message' => 'Doctor profile updated successfully',
        'data' => $profile
    ]);
}

}
