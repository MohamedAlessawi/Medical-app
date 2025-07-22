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

    $existingProfile = $this->doctorProfileRepo->getByUserId($user->id);
    $isNew = !$existingProfile;

    // ✅ القواعد حسب إذا كانت أول مرة أو لا
    $rules = [
        'profile_photo' => 'nullable|image|max:2048',
        'birthdate' => $isNew ? 'required|date' : 'nullable|date',
        'gender' => $isNew ? 'required|in:male,female' : 'nullable|in:male,female',
        'address' => $isNew ? 'required|string|max:255' : 'nullable|string|max:255',

        'about_me' => $isNew ? 'required|string' : 'nullable|string',
        'specialty_id' => $isNew ? 'required|exists:specialties,id' : 'nullable|exists:specialties,id',
        'years_of_experience' => $isNew ? 'required|integer' : 'nullable|integer',
        'specialty_id' => $isNew ? 'required|exists:specialties,id' : 'nullable|exists:specialties,id',
        'certificate' => $isNew ? 'required|file|mimes:pdf,jpg,jpeg,png' : 'nullable|file',
        'appointment_duration' => $isNew ? 'required|integer' : 'nullable|integer',
    ];

    $validated = Validator::make($request->all(), $rules)->validate();

    // ✅ معالجة الملفات
    $certificatePath = $this->handleFileUpload($request, 'certificate', 'certificates');
    $profilePicPath = $this->handleFileUpload($request, 'profile_photo', 'profile_photos');

    // ✅ تحديث جدول doctor_profiles
    $doctorData = [
        'user_id' => $user->id,
        'about_me' => $validated['about_me'] ?? null,
        'specialty_id' => $validated['specialty_id'] ?? null,
        'years_of_experience' => $validated['years_of_experience'] ?? null,
    ];

    if ($certificatePath) {
        $doctorData['certificate'] = $certificatePath;
    }

    $profile = $this->doctorProfileRepo->updateOrCreate(
        ['user_id' => $user->id],
        $doctorData
    );

    // ✅ تحديث جدول users
    $user->gender = $validated['gender'] ?? $user->gender;
    $user->birthdate = $validated['birthdate'] ?? $user->birthdate;
    $user->address = $validated['address'] ?? $user->address;

    if ($profilePicPath) {
        $user->profile_photo = $profilePicPath;
    }

    $user->save();

    return $this->unifiedResponse(true, 'Doctor profile saved successfully', [
    'doctor_profile' => [
        'about_me' => $profile->about_me,
        'years_of_experience' => $profile->years_of_experience,
        'specialty_id' => $profile->specialty_id,
        'certificate' => $profile->certificate,
        "appointment_duration"=> $profile->appointment_duration,
        'status' => $profile->status,
    ],
    'user' => [
        'full_name' => $user->full_name,
        'gender' => $user->gender,
        'birthdate' => $user->birthdate,
        'profile_photo' => $user->profile_photo,
        'address' => $user->address,
    ]
]);

}


    public function show()
    {
        $profile = $this->doctorProfileRepo->getByUserId(Auth::id());

        if (!$profile) {
            return $this->unifiedResponse(false, 'Doctor profile not found');
        }

        return $this->unifiedResponse(true, 'Doctor profile fetched successfully', $profile);
    }
}

