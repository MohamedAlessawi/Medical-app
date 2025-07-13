<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Patient\PatientProfileService;

class PatientProfileController extends Controller
{
    protected $service;

    public function __construct(PatientProfileService $service)
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
        'phone' => 'nullable|string|max:20',
        //'location' => 'nullable|string|max:100',
        'gender' => 'nullable|in:male,female',
        'blood_type' => 'nullable|string|max:5',
        'birth_date' => 'nullable|date',
    ]);

    $userId = $request->user()->id;
    $updated = $this->service->updateProfile($userId, $validated);

    return response()->json([
        'success' => true,
        'message' => 'Profile updated successfully',
        'data' => $updated
    ]);
}

}
