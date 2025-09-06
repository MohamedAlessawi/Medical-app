<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Models\UserVerify;
use App\Models\Center;
use App\Models\Secretary;
use App\Models\Doctor;
use App\Traits\ApiResponseTrait;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class AdminUserService
{
    use ApiResponseTrait;

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function addUserRole($request)
    {
        try {
            // $validator = Validator::make($request->all(), [
            //     'full_name' => 'required|string|max:255',
            //     'email' => 'required|string|email|max:255',
            //     'phone' => 'required|string|max:15',
            //     'role' => 'required|exists:roles,name',
            //     'verify_email' => 'nullable|boolean',
            // ]);

            // if ($validator->fails()) {
            //     return $this->unifiedResponse(false, 'Validation failed.', [], $validator->errors()->toArray(), 422);
            // }

            $centerId = Auth::user()->adminCenters->first()->center_id;

            $data = $request->validated();
            $user = $this->userRepository->findByEmailOrPhone($request->email ?? $request->phone);

            if ($user) {
                // User exists, add new role
                $this->userRepository->attachRole($user->id, $request->role);

                if ($data['role'] === 'secretary') {
                    Secretary::firstOrCreate([
                        'user_id' => $user->id,
                        'center_id' => $centerId,
                    ]);
                }
                if ($data['role'] === 'doctor') {
                    Doctor::firstOrCreate([
                        'user_id' => $user->id,
                        'center_id' => $data['center_id'],
                    ]);
                }

                return $this->unifiedResponse(true, 'Role added successfully.', ['user_id' => $user->id], [], 200);
            }

            // Create new user
            $password = '12345678';
            $userData = [
                'full_name' => $request->full_name,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => Hash::make($password),
                'ip_address' => $request->ip(),
                'email_verified_at' => $request->verify_email ? now() : null,
            ];

            // if ($request->verify_email) {
            //     $userData['email_verified_at'] = now(); // Manual verification
            // }

            $user = $this->userRepository->create($userData);
            $this->userRepository->attachRole($user->id, $request->role);

            if ($data['role'] === 'secretary') {
                Secretary::create([
                    'user_id' => $user->id,
                    'center_id' => $centerId,
                ]);
            }
            if ($data['role'] === 'doctor') {
                Doctor::firstOrCreate([
                    'user_id' => $user->id,
                    'center_id' => $data['center_id'],
                ]);
            }


            // Generate verification code if not manually verified
            $code = null;
            if (!$request->verify_email) {
                $code = Str::random(6);
                UserVerify::create(['user_id' => $user->id, 'token' => $code]);
                Cache::put($request->ip(), [$code, $request->email], now()->addMinutes(10));
            }

            // Send email with credentials
            // Mail::send('emails.new_user', [
            //     'email' => $user->email,
            //     'password' => $password,
            //     'verify_email' => $request->verify_email,
            //     'code' => $code
            // ], function($message) use ($user) {
            //     $message->to($user->email);
            //     $message->subject('Your New Account Credentials');
            // });

            $message = $request->verify_email ? 'User created and email verified.' : 'User created, verification code sent.';
            return $this->unifiedResponse(true, $message, ['user_id' => $user->id], [], 201);
        } catch (Exception $e) {
            Log::error('Error creating user or adding role: ' . $e->getMessage());
            return $this->unifiedResponse(false, 'Failed to create user or add role.', [], ['error' => $e->getMessage()], 500);
        }
    }
}
