<?php

namespace App\Services\Secretary;

use App\Models\{User, PatientProfile, UserCenter};
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Exception;

class PatientService
{
    use ApiResponseTrait;

    protected $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }


    public function createPatientFromSecretary(array $data)
    {
        try {
            DB::beginTransaction();

            $existingUser = $this->userRepository->findByEmailOrPhone($data['email'] ?? $data['phone']);

            if ($existingUser) {
                $centerId = Auth::user()->secretaries->first()->center_id;

                $alreadyLinked = UserCenter::where('user_id', $existingUser->id)
                    ->where('center_id', $centerId)
                    ->exists();

                if ($alreadyLinked) {
                    return $this->unifiedResponse(false, 'User already exists and is linked to this center.', [
                        'user_id' => $existingUser->id,
                    ], [], 409);
                }

                $this->userRepository->attachRole($existingUser->id, 'patient');

                UserCenter::firstOrCreate([
                    'user_id' => $existingUser->id,
                    'center_id' => Auth::user()->secretaries->first()->center_id
                ]);

                $fieldsToUpdate = [];
                if (is_null($existingUser->birthdate) && !empty($data['birthdate'])) {
                    $fieldsToUpdate['birthdate'] = $data['birthdate'];
                }
                if (is_null($existingUser->gender) && !empty($data['gender'])) {
                    $fieldsToUpdate['gender'] = $data['gender'];
                }
                if (is_null($existingUser->address) && !empty($data['address'])) {
                    $fieldsToUpdate['address'] = $data['address'];
                }

                if (!empty($fieldsToUpdate)) {
                    $existingUser->update($fieldsToUpdate);
                }

                DB::commit();
                return $this->unifiedResponse(true, 'Existing user attached to center as patient.', ['user_id' => $existingUser->id], [], 200);
            }

            $password = "12345678";

            $user = $this->userRepository->create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'gender' => $data['gender'],
                'birthdate' => $data['birthdate'],
                'address' => $data['address'],
                'password' => Hash::make($password),
                'ip_address' => request()->ip(),
            ]);

            $this->userRepository->attachRole($user->id, 'patient');

            UserCenter::create([
                'user_id' => $user->id,
                'center_id' => Auth::user()->secretaries->first()->center_id
            ]);

            Mail::raw("Your account has been created. Email: {$user->email}, Password: {$password}", function ($message) use ($user) {
                $message->to($user->email)->subject('New Patient Account Created');
            });

            DB::commit();

            return $this->unifiedResponse(true, 'New patient created successfully.', ['user_id' => $user->id], [], 201);

        } catch (Exception $e) {
            DB::rollBack();
            return $this->unifiedResponse(false, 'Failed to create patient.', [], ['error' => $e->getMessage()], 500);
        }
    }

    public function getAllPatientsForSecretary()
    {
        $centerId = Auth::user()->secretaries->first()->center_id;

        $patients = User::whereHas('userCenters', fn($q) => $q->where('center_id', $centerId))
            ->whereHas('roles', fn($q) => $q->where('name', 'patient'))
            ->with('patientProfile')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'birthdate' => $user->birthdate,
                'gender' => $user->gender,
                'address' => $user->address,
                'patient_profile' => optional($user->patientProfile)->only(['condition', 'last_visit', 'status']),
            ]);

        return $this->unifiedResponse(true, 'Patients fetched successfully.', $patients);
    }

    public function getPatientDetails($id)
    {
        $user = User::with('patientProfile')->find($id);
        if (!$user) {
            return $this->unifiedResponse(false, 'Patient not found.', [], [], 404);
        }

        $data = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'birthdate' => $user->birthdate,
            'gender' => $user->gender,
            'address' => $user->address,
            'patient_profile' => optional($user->patientProfile)->only(['condition', 'last_visit', 'status']),
        ];

        return $this->unifiedResponse(true, 'Patient details fetched.', $data);
    }

    public function updatePatientUnified($id, array $data)
    {
        $user = User::find($id);

        if (!$user) {
            return $this->unifiedResponse(false, 'Patient not found.', [], [], 404);
        }

        $user->update([
            'full_name' => $data['full_name'] ?? $user->full_name,
            'phone' => $data['phone'] ?? $user->phone,
            'gender' => $data['gender'] ?? $user->gender,
            'birthdate' => $data['birthdate'] ?? $user->birthdate,
            'address' => $data['address'] ?? $user->address,
        ]);

        $profile = PatientProfile::updateOrCreate(
            ['user_id' => $user->id],
            [
                'condition' => $data['condition'] ?? null,
                'last_visit' => $data['last_visit'] ?? null,
                'status' => $data['status'] ?? null,
            ]
        );

        return $this->unifiedResponse(true, 'Patient updated successfully.', [
            'user_id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'gender' => $user->gender,
            'birthdate' => $user->birthdate,
            'age' => $user->birthdate ? \Carbon\Carbon::parse($user->birthdate)->age : null,
            'address' => $user->address,
            'condition' => $profile->condition,
            'last_visit' => $profile->last_visit,
            'status' => $profile->status,
        ]);
    }


    public function updatePatient($id, array $data)
    {
        $user = User::find($id);
        if (!$user) {
            return $this->unifiedResponse(false, 'Patient not found.', [], [], 404);
        }

        $user->update($data);

        return $this->unifiedResponse(true, 'Patient updated successfully.', $user->only([
            'id', 'full_name', 'email', 'phone', 'birthdate', 'gender', 'address'
        ]));
    }

    public function updatePatientProfile($id, array $data)
    {
        $profile = PatientProfile::updateOrCreate(
            ['user_id' => $id],
            $data
        );

        return $this->unifiedResponse(true, 'Profile updated successfully.', $profile->only([
            'condition', 'last_visit', 'status'
        ]));
    }

    public function searchPatients($query)
    {
        $centerId = Auth::user()->secretaries->first()->center_id;

        $results = User::whereHas('userCenters', function ($q) use ($centerId) {
            $q->where('center_id', $centerId);
        })
        ->whereHas('roles', function ($q) {
            $q->where('name', 'patient');
        })
        ->where(function ($q) use ($query) {
            $q->where('full_name', 'like', "%$query%")
            ->orWhere('phone', 'like', "%$query%");
        })
        ->orWhereHas('patientProfile', function ($q) use ($query) {
            $q->where('condition', 'like', "%$query%");
        })
        ->with('patientProfile')
        ->get();

        return $this->unifiedResponse(true, 'Search results', $results->only([
            'id', 'full_name', 'email', 'phone', 'birthdate', 'gender', 'address'
        ]));
    }


}
