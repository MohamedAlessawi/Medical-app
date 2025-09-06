<?php
namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserVerify;
use App\Traits\ApiResponseTrait;
use App\Repositories\UserRepository;
use App\Traits\FileUploadTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Exception;

class RegisterService
{
    use FileUploadTrait, ApiResponseTrait;

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function register($request)
    {
        try {
            $profilePhotoPath = $this->handleFileUpload($request, 'profile_photo', 'profile_photos');

            $userData = [
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($request->password),
                'profile_photo' => $profilePhotoPath['path'] ?? null,
                'ip_address' => $request->ip(),
                'birthdate' => $request->birthdate,
                'gender'    => $request->gender,
                'address'   => $request->address,

            ];

            $user = $this->userRepository->create($userData);
            $role = $request->role ?? 'patient';
            $this->userRepository->attachRole($user->id, $role);

            $code = Str::random(6);
            UserVerify::create(['user_id' => $user->id, 'token' => $code]);
            Cache::put($request->ip(), [$code, $request->email], now()->addMinutes(3));

            Mail::send('emails.verifyEmail', ['token' => $code], function($message) use ($request) {
                $message->to($request->email);
                $message->subject('Email Verification Code');
            });

            return $this->unifiedResponse(true, 'Registration successful, please check your email for verification code.', ['user_id' => $user->id,'profile_photo'   => $user->profile_photo,           // path المخزون
            'profile_photo_url' => $upload['url'] ?? ($user->profile_photo ? asset('storage/'.$user->profile_photo) : null),], [], 201);

        } catch (Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return $this->unifiedResponse(false, 'Registration failed. Please try again later.', [], [], 500);
        }
    }
    public function registerDoctor($request)
{
    //dd($request->all());
    try {
        //\Illuminate\Support\Facades\Log::info('Request data', $request->all());

    $validated = $request->validate([
        'full_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users,email',
        'phone' => 'required|string|unique:users,phone',
        'password' => 'required|string|min:6|confirmed',

        'specialty_id' => 'required|exists:specialties,id',
        'certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:4096',
    ]);

        // ✅ رفع الملفات
        $certificatePath  = $this->handleFileUpload($request, 'certificate', 'certificates');

        // ✅ إنشاء المستخدم
        $userData = [
            'full_name'     => $validated['full_name'],
            'email'         => $validated['email'],
            'phone'         => $validated['phone'],
            'password'      => Hash::make($validated['password']),
            'ip_address'    => $request->ip(),
        ];

            /** @var \App\Models\User $user */

        $user = $this->userRepository->create($userData);

        // ✅ إسناد الدور
        $this->userRepository->attachRole($user->id, 'doctor');

        // ✅ إنشاء ملف الطبيب
        \App\Models\DoctorProfile::create([
            'user_id'             => $user->id,
            'specialty_id'        => $validated['specialty_id'],
            'certificate'         => $certificatePath['path'] ?? null,
            'status'              => 'pending',
        ]);

        // ✅ إرسال كود التحقق
        $code = Str::random(6);
        UserVerify::create([
            'user_id' => $user->id,
            'token'   => $code,
        ]);

        Cache::put($request->ip(), [$code, $validated['email']], now()->addMinutes(3));

        Mail::send('emails.verifyEmail', ['token' => $code], function ($message) use ($validated) {
            $message->to($validated['email']);
            $message->subject('Email Verification Code');
        });

        return $this->unifiedResponse(true, 'Registration successful. Please check your email for verification code.', [
            'user_id' => $user->id,
        ], [], 201);

    } catch (\Exception $e) {
    return $this->unifiedResponse(false, 'Registration failed.', [], [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ], 500);
}

}
}
