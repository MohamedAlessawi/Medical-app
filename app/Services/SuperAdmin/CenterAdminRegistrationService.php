<?php

namespace App\Services\SuperAdmin;

use App\Models\User;
use App\Models\Center;
use App\Models\AdminCenter;
use App\Models\License;
use App\Models\Subscription;
use App\Models\Role;
use App\Models\UserRole;
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Exception;

class CenterAdminRegistrationService
{
    use ApiResponseTrait;

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function registerCenterWithAdmin($request)
    {
        DB::beginTransaction();
        try {
            $user = $this->userRepository->findByEmailOrPhone($request->email ?? $request->phone);

            if (!$user) {
                $password = Str::random(12);
                $userData = [
                    'full_name' => $request->full_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => Hash::make($password),
                    'ip_address' => $request->ip(),
                ];
                $user = $this->userRepository->create($userData);
                $isNewUser = true;
            } else {
                $isNewUser = false;
                $password = null;
            }
            $this->userRepository->attachRole($user->id, 'center_admin');

            $center = Center::create([
                'name' => $request->center_name,
                'location' => $request->center_location,
            ]);

            AdminCenter::create([
                'user_id' => $user->id,
                'center_id' => $center->id,
            ]);

            License::create([
                'user_id' => $user->id,
                'status' => 'pending',
                'issued_by' => 'system',
                'issue_date' => now(),
            ]);

            Subscription::create([
                'user_id' => $user->id,
                'center_id' => $center->id,
                'amount' => $request->amount ?? 0,
                'status' => 'pending',
                'payment_date' => null,
            ]);

            if ($isNewUser) {
                Mail::send('emails.center_admin_account', [
                    'email' => $user->email,
                    'password' => $password,
                    'center_name' => $center->name,
                ], function($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('Your Center Admin Account Information');
                });
            } else {
                Mail::send('emails.center_admin_added', [
                    'center_name' => $center->name,
                ], function($message) use ($user) {
                    $message->to($user->email);
                    $message->subject('You have been assigned as a Center Admin');
                });
            }

            DB::commit();

            return $this->unifiedResponse(true, 'Center and admin registered successfully.', [
                'user_id' => $user->id,
                'center_id' => $center->id,
            ], [], 201);

        } catch (Exception $e) {
            DB::rollBack();
            \Log::error('Error in center admin registration: ' . $e->getMessage());
            return $this->unifiedResponse(false, 'Failed to register center and admin.', [], ['error' => $e->getMessage()], 500);
        }
    }
}
