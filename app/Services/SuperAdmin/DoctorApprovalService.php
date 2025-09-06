<?php

namespace App\Services\SuperAdmin;

use App\Repositories\Doctor\DoctorProfileRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Mail;

class DoctorApprovalService
{
    use ApiResponseTrait;

    protected $doctorRepo;

    public function __construct(DoctorProfileRepository $doctorRepo)
    {
        $this->doctorRepo = $doctorRepo;
    }

    public function listPending()
    {
        $doctors = $this->doctorRepo->getPendingProfiles();

        $filtered = $doctors->map(function ($doctor) {

            $certificateUrl = $doctor->certificate
                    ? asset('storage/' . ltrim($doctor->certificate, '/'))
                    : null;

            return [
                'doctor_profile' => [
                    'id' => $doctor->id,
                    'specialty_id' => $doctor->specialty_id,
                    'certificate' => $certificateUrl,
                    'status' => $doctor->status,
                ],
                'user' => [
                    'full_name' => $doctor->user->full_name,
                ]
            ];
        });

        return $this->unifiedResponse(true, 'Pending doctors fetched', $filtered);
    }


    // public function approve($id)
    // {
    //     $profile = $this->doctorRepo->updateStatus($id, 'approved');
    //     return $this->unifiedResponse(true, 'Doctor approved', $profile);
    // }

    // public function reject($id)
    // {
    //     $profile = $this->doctorRepo->updateStatus($id, 'rejected');
    //     return $this->unifiedResponse(true, 'Doctor rejected', $profile);
    // }


    public function approve($id)
    {
        $profile = $this->doctorRepo->updateStatus($id, 'approved');

        // إرسال الإيميل للدكتور عند القبول
        if ($profile && $profile->user?->email) {
            $email = $profile->user->email;
            $name  = $profile->user->full_name;

            Mail::raw(
                "Hello {$name},\n\nYour doctor account has been approved. You can now log in to the system.\n\nBest regards,\nMedical Booking System",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Doctor Account Approved');
                }
            );
        }

        return $this->unifiedResponse(true, 'Doctor approved', $profile);
    }

    public function reject($id)
    {
        $profile = $this->doctorRepo->updateStatus($id, 'rejected');

        // إرسال الإيميل للدكتور عند الرفض
        if ($profile && $profile->user?->email) {
            $email = $profile->user->email;
            $name  = $profile->user->full_name;

            Mail::raw(
                "Hello {$name},\n\nWe are sorry to inform you that your doctor account request has been rejected.\n\nBest regards,\nMedical Booking System",
                function ($message) use ($email) {
                    $message->to($email)
                        ->subject('Doctor Account Rejected');
                }
            );
        }

        return $this->unifiedResponse(true, 'Doctor rejected', $profile);
    }


    public function listAllDoctors()
    {
        $doctors = \App\Models\User::whereHas('roles', fn($q) => $q->where('name','doctor'))
            ->with(['doctorProfile.specialty'])
            ->orderBy('full_name')
            ->get();

        $data = $doctors->map(function ($u) {
            $profile = $u->doctorProfile;
            $certificateUrl = $profile?->certificate
                ? asset('storage/' . ltrim($profile->certificate, '/'))
                : null;

            return [
                'user' => [
                    'id'        => $u->id,
                    'full_name' => $u->full_name,
                    'email'     => $u->email,
                    'phone'     => $u->phone,
                    'birthdate' => $u->birthdate,
                    'gender'    => $u->gender,
                    'address'   => $u->address
                ],
                'doctor_profile' => $profile ? [
                    'id'                   => $profile->id,
                    'specialty_id'         => $profile->specialty_id,
                    'specialty_name'       => $profile->specialty?->name,
                    'about_me'             => $profile->about_me,
                    'years_of_experience'  => $profile->years_of_experience,
                    'status'               => $profile->status,
                    'certificate'          => $certificateUrl,
                ] : null
            ];
        });

        return $this->unifiedResponse(true, 'All doctors fetched', $data);
    }

}
