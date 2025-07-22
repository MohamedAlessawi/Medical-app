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
            $existingUser = $this->userRepository->findByEmailOrPhone($request->email ?? $request->phone);


            $password = "12345678" ;

            if ($existingUser) {
                $user = $existingUser;

                $adminRole = Role::where('name', 'admin')->first();
                UserRole::firstOrCreate([
                    'user_id' => $user->id,
                    'role_id' => $adminRole->id,
                ]);

                if (!$user->email_verified_at) {
                    $user->email_verified_at = now();
                    $user->save();
                }
            } else {
                $user = User::create([
                    'full_name' => $request->full_name,
                    'email' => $request->email,
                    'phone' => $request->phone,
                    'password' => Hash::make($password),
                    'ip_address' => $request->ip(),
                    'email_verified_at' => now(),
                ]);

                $adminRole = Role::where('name', 'admin')->first();
                UserRole::create([
                    'user_id' => $user->id,
                    'role_id' => $adminRole->id,
                ]);
            }

            $center = Center::create([
                'name' => $request->center_name,
                'location' => $request->center_location,
            ]);

            AdminCenter::create([
                'user_id' => $user->id,
                'center_id' => $center->id,
            ]);

            Subscription::create([
                'user_id' => $user->id,
                'center_id' => $center->id,
                'amount' => $request->amount,
                'status' => 'pending',
                'payment_date' => now(),
            ]);

            $filePath = null;
            if ($request->hasFile('license_file')) {
                $file = $request->file('license_file');
                $filePath = $file->store('licenses', 'public');
            }

            License::create([
                'user_id' => $user->id,
                'center_id' => $center->id,
                'status' => 'pending',
                'issued_by' => $request->issued_by,
                'issue_date' => $request->issue_date,
                'file_path' => $filePath,
            ]);

            Mail::send('emails.new_admin', [
                'email' => $user->email,
                'password' => $user ? '[Use your existing password]' : $password,
                'center_name' => $center->name
            ], function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Center Admin Account Created');
            });

            DB::commit();

            return $this->unifiedResponse(true, 'Center admin registered successfully.', [
                'user_id' => $user->id,
                'center_id' => $center->id
            ], [], 201);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->unifiedResponse(false, 'Failed to register center admin.', [], [
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
