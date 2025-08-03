<?php
namespace App\Services\Secretary;

use App\Models\{User, UserCenter};
use App\Repositories\UserRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

use Exception;
use Carbon\Carbon;

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

            $centerId = Auth::user()->secretaries->first()->center_id;
            $existingUser = $this->userRepository->findByEmailOrPhone($data['email'] ?? $data['phone']);

            if ($existingUser) {
                $alreadyLinked = UserCenter::where('user_id', $existingUser->id)
                    ->where('center_id', $centerId)
                    ->exists();

                if ($alreadyLinked) {
                    return $this->unifiedResponse(false, 'User already exists and is linked to this center.', [
                        'user_id' => $existingUser->id,
                    ], [], 409);
                }

                $this->userRepository->attachRole($existingUser->id, 'patient');

                UserCenter::create([
                    'user_id'   => $existingUser->id,
                    'center_id' => $centerId,
                    'condition' => $data['condition'] ?? null,
                    'last_visit'=> $data['last_visit'] ?? null,
                    'status'    => $data['status'] ?? null,
                ]);

                DB::commit();
                return $this->unifiedResponse(true, 'Existing user attached to center as patient.', ['user_id' => $existingUser->id], [], 200);
            }

            $password = "12345678";

            $user = $this->userRepository->create([
                'full_name' => $data['full_name'],
                'email'     => $data['email'],
                'phone'     => $data['phone'],
                'gender'    => $data['gender'],
                'birthdate' => $data['birthdate'],
                'address'   => $data['address'],
                'password'  => Hash::make($password),
                'ip_address'=> request()->ip(),
            ]);

            $this->userRepository->attachRole($user->id, 'patient');

            UserCenter::create([
                'user_id'   => $user->id,
                'center_id' => $centerId,
                'condition' => $data['condition'] ?? null,
                'last_visit'=> $data['last_visit'] ?? null,
                'status'    => $data['status'] ?? null,
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
            ->with(['userCenters' => fn($q) => $q->where('center_id', $centerId)])
            ->get()
            ->map(function ($user) use ($centerId) {
                $uc = $user->userCenters->first();
                return [
                    'id'         => $user->id,
                    'full_name'  => $user->full_name,
                    'email'      => $user->email,
                    'phone'      => $user->phone,
                    'age'        => $user->birthdate ? Carbon::parse($user->birthdate)->age : null,
                    'condition'  => $uc->condition ?? null,
                    'last_visit' => $uc->last_visit ?? null,
                    'status'     => $uc->status ?? null,
                ];
            });

        return $this->unifiedResponse(true, 'Patients fetched successfully.', $patients);
    }

    public function getPatientDetails($id)
    {
        $centerId = Auth::user()->secretaries->first()->center_id;

        $user = User::whereHas('userCenters', fn($q) => $q->where('center_id', $centerId))
            ->with(['userCenters' => fn($q) => $q->where('center_id', $centerId)])
            ->find($id);

        if (!$user) {
            return $this->unifiedResponse(false, 'Patient not found.', [], [], 404);
        }

        $uc = $user->userCenters->first();

        return $this->unifiedResponse(true, 'Patient details fetched.', [
            'id'         => $user->id,
            'full_name'  => $user->full_name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'age'        => $user->birthdate ? Carbon::parse($user->birthdate)->age : null,
            'condition'  => $uc->condition ?? null,
            'last_visit' => $uc->last_visit ?? null,
            'status'     => $uc->status ?? null,
        ]);
    }

    public function updatePatientUnified($id, array $data)
    {
        $centerId = Auth::user()->secretaries->first()->center_id;

        $user = User::find($id);

        if (!$user) {
            return $this->unifiedResponse(false, 'Patient not found.', [], [], 404);
        }

        $uc = UserCenter::where('user_id', $id)->where('center_id', $centerId)->first();

         if (!$uc) {
            return $this->unifiedResponse(false, 'UserCenter record not found for this patient in your center.', [], [], 404);
        }


        UserCenter::where('user_id', $id)
            ->where('center_id', $centerId)
            ->update([
                'condition'  => $data['condition'] ?? $uc->condition,
                'last_visit' => $data['last_visit'] ?? $uc->last_visit,
                'status'     => $data['status'] ?? $uc->status,
            ]);

        $uc = UserCenter::where('user_id', $id)
            ->where('center_id', $centerId)
            ->first();

        return $this->unifiedResponse(true, 'Patient updated successfully.', [
            'id'         => $user->id,
            'full_name'  => $user->full_name,
            'email'      => $user->email,
            'phone'      => $user->phone,
            'age'        => $user->birthdate ? Carbon::parse($user->birthdate)->age : null,
            'condition'  => $uc->condition ?? null,
            'last_visit' => $uc->last_visit ?? null,
            'status'     => $uc->status ?? null,
        ]);
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
            ->where(function ($q) use ($query, $centerId) {
                $q->where('full_name', 'like', "%$query%")
                ->orWhere('phone', 'like', "%$query%")
                ->orWhere('email', 'like', "%$query%")
                ->orWhereHas('userCenters', function ($uc) use ($query, $centerId) {
                    $uc->where('center_id', $centerId)
                        ->where(function ($sub) use ($query) {
                            $sub->where('condition', 'like', "%$query%")
                                ->orWhere('status', 'like', "%$query%");
                        });
                });
            })
            ->with(['userCenters' => fn($q) => $q->where('center_id', $centerId)])
            ->get();

        if ($results->isEmpty()) {
            return $this->unifiedResponse(false, 'No matching patients found.', [], [], 404);
        }

        $formatted = $results->map(function ($user) {
            $uc = $user->userCenters->first();
            return [
                'id'         => $user->id,
                'full_name'  => $user->full_name,
                'email'      => $user->email,
                'phone'      => $user->phone,
                'age'        => $user->birthdate ? Carbon::parse($user->birthdate)->age : null,
                'condition'  => $uc->condition ?? null,
                'last_visit' => $uc->last_visit ?? null,
                'status'     => $uc->status ?? null,
            ];
        });

        return $this->unifiedResponse(true, 'Search results', $formatted);
    }

}
