<?php

namespace App\Services\Doctor;

use App\Repositories\Doctor\DoctorProfileRepository;
use Illuminate\Support\Facades\Auth;
use App\Traits\FileUploadTrait;
use App\Traits\ApiResponseTrait;
class DoctorProfileService
{
    use FileUploadTrait;
    use ApiResponseTrait;

    protected $doctorProfileRepo;

    public function __construct(DoctorProfileRepository $doctorProfileRepo)
    {
        $this->doctorProfileRepo = $doctorProfileRepo;
    }

    public function storeOrUpdate($request)
    {
        $user = Auth::user();

        // ✅ رفع ملف الشهادة إن وُجد
        $certificatePath = $this->handleFileUpload($request, 'certificate', 'certificates');

        $data = [
            'user_id' => $user->id,
            'about_me' => $request->input('about_me'),
            'years_of_experience' => $request->input('years_of_experience'),
        ];

        // فقط إذا تم رفع شهادة جديدة
        if ($certificatePath) {
            $data['certificate'] = $certificatePath;
        }

        $profile = $this->doctorProfileRepo->updateOrCreate(
            ['user_id' => $user->id],
            $data
        );

        return $this->unifiedResponse(true, 'Doctor profile saved successfully', $profile);
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

