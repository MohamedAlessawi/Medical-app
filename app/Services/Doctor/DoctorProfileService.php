<?php

namespace App\Services\Doctor;

use App\Repositories\Doctor\DoctorProfileRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Traits\FileUploadTrait;
use App\Traits\ApiResponseTrait;
use App\Models\User;

class DoctorProfileService
{
    use FileUploadTrait, ApiResponseTrait;

    protected $doctorProfileRepo;

    public function __construct(DoctorProfileRepository $doctorProfileRepo)
    {
        $this->doctorProfileRepo = $doctorProfileRepo;
    }

    public function storeOrUpdate($request)
{
    /** @var \App\Models\User $user */
    $user = Auth::user();

    $profile = $this->doctorProfileRepo->getByUserId($user->id);

    if (!$profile) {
        return $this->unifiedResponse(false, 'Doctor profile not found. Please complete registration first.', [], [], 404);
    }

    $rules = [
        'profile_photo' => 'nullable|image|max:2048',
        'birthdate' => 'nullable|date',
        'gender' => 'nullable|in:male,female',
        'address' => 'nullable|string|max:255',
        'full_name' => 'nullable|string|max:255',

        'about_me' => 'nullable|string',
        'specialty_id' => 'nullable|exists:specialties,id',
        'years_of_experience' => 'nullable|integer|min:0',
        'certificate' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:4096',
        'appointment_duration' => 'nullable|integer|min:1',
    ];

    $validated = Validator::make($request->all(), $rules)->validate();

    $certificatePath = $this->handleFileUpload($request, 'certificate', 'certificates');
    $profilePhotoPath = $this->handleFileUpload($request, 'profile_photo', 'profile_photos');

    $profile->update([
        'about_me' => $validated['about_me'] ?? $profile->about_me,
        'specialty_id' => $validated['specialty_id'] ?? $profile->specialty_id,
        'years_of_experience' => $validated['years_of_experience'] ?? $profile->years_of_experience,
        'appointment_duration' => $validated['appointment_duration'] ?? $profile->appointment_duration,
        'certificate' => $certificatePath ?? $profile->certificate,
        'status' => $certificatePath ? 'pending' : $profile->status,
    ]);

    $statusNote = $certificatePath ? 'Status reverted to pending due to certificate update.' : null;

    $user->update([
        'full_name' => $validated['full_name'] ?? $user->full_name,
        'gender' => $validated['gender'] ?? $user->gender,
        'birthdate' => $validated['birthdate'] ?? $user->birthdate,
        'address' => $validated['address'] ?? $user->address,
        'profile_photo' => $profilePhotoPath ?? $user->profile_photo,
    ]);

    return $this->unifiedResponse(true, 'Doctor profile updated successfully', [
        'doctor_profile' => $profile->only([
            'about_me',
            'years_of_experience',
            'specialty_id',
            'certificate',
            'appointment_duration',
            'status'
        ]),
        'user' => $user->only([
            'id',
            'full_name',
            'email',
            'phone',
            'gender',
            'birthdate',
            'profile_photo',
            'address'
        ]),
        'note' => $statusNote
    ]);
}

public function show()
{
    $user = Auth::user();

    $profile = $this->doctorProfileRepo->getByUserId($user->id);

    if (!$profile) {
        return $this->unifiedResponse(false, 'Doctor profile not found');
    }

    return $this->unifiedResponse(true, 'Doctor profile fetched successfully', [
        'doctor_profile' => [
            'about_me' => $profile->about_me,
            'years_of_experience' => $profile->years_of_experience,
            'specialty_id' => $profile->specialty_id,
            'certificate' => $profile->certificate,
            'appointment_duration' => $profile->appointment_duration,
            'status' => $profile->status,
        ],
        'user' => [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_photo' => $user->profile_photo,
            'birthdate' => $user->birthdate,
            'gender' => $user->gender,
            'address' => $user->address,
        ]
    ]);
}
}
