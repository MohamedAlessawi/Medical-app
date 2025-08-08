<?php

namespace App\Services\Secretary;

use App\Models\{Doctor, WorkingHour, User};
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use Exception;

class DoctorService
{
    use ApiResponseTrait;

    public function getDoctorsInCenter()
    {
        try {
            $centerId = Auth::user()->secretaries->first()->center_id;

            $doctors = Doctor::where('center_id', $centerId)
                ->with([
                    'user:id,full_name,email,address',
                    'user.doctorProfile:id,user_id,about_me,years_of_experience,specialty_id',
                    'user.doctorProfile.specialty:id,name',
                    'workingHours:id,doctor_id,day_of_week,start_time,end_time',
                    'appointments:id,doctor_id,patient_id'
                ])
                ->get()
                ->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'user_id' => $doctor->user_id,
                        'full_name' => 'Dr. ' . $doctor->user->full_name,
                        'email' => $doctor->user->email,
                        'address' => $doctor->user->address ?? null,
                        'about_me' => $doctor->user->doctorProfile->about_me ?? null,
                        'years_of_experience' => $doctor->user->doctorProfile->years_of_experience ?? null,
                        'specialty' => $doctor->user->doctorProfile->specialty->name ?? null,
                        'working_hours' => $doctor->workingHours->map(function ($hour) {
                            return [
                                'day_of_week' => $hour->day_of_week,
                                'start_time' => $hour->start_time,
                                'end_time' => $hour->end_time,
                            ];
                        }),
                        'total_patients' => $doctor->appointments->unique('patient_id')->count(),
                        'total_appointments' => $doctor->appointments->count(),
                    ];
                });

            return $this->unifiedResponse(true, 'Doctors retrieved successfully.', $doctors);

        } catch (Exception $e) {
            Log::error('Failed to fetch doctors: ' . $e->getMessage());
            return $this->unifiedResponse(false, 'Failed to fetch doctors.', [], ['error' => $e->getMessage()], 500);
        }
    }

    public function getDoctorDetails($id)
    {
        try {
            $doctor = Doctor::with([
                    'user:id,full_name,email,address',
                    'user.doctorProfile:id,user_id,about_me,years_of_experience,specialty_id',
                    'user.doctorProfile.specialty:id,name',
                    'workingHours:id,doctor_id,day_of_week,start_time,end_time',
                    'appointments:id,doctor_id,patient_id'
                ])
                ->findOrFail($id);

            $data = [
                'id' => $doctor->id,
                'user_id' => $doctor->user_id,
                'full_name' => 'Dr. ' . $doctor->user->full_name,
                'email' => $doctor->user->email,
                'address' => $doctor->user->address ?? null,
                'about_me' => $doctor->user->doctorProfile->about_me ?? null,
                'years_of_experience' => $doctor->user->doctorProfile->years_of_experience ?? null,
                'specialty' => $doctor->user->doctorProfile->specialty->name ?? null,
                'working_hours' => $doctor->workingHours->map(function ($hour) {
                    return [
                        'day_of_week' => $hour->day_of_week,
                        'start_time' => $hour->start_time,
                        'end_time' => $hour->end_time,
                    ];
                }),
                'total_patients' => $doctor->appointments->unique('patient_id')->count(),
                'total_appointments' => $doctor->appointments->count(),
            ];

            return $this->unifiedResponse(true, 'Doctor details fetched successfully.', $data);

        } catch (Exception $e) {
            return $this->unifiedResponse(false, 'Doctor not found.', [], ['error' => $e->getMessage()], 404);
        }
    }

    public function getWorkingHours($doctorId)
    {
        try {
            $hours = WorkingHour::where('doctor_id', $doctorId)->get();
            return $this->unifiedResponse(true, 'Working hours fetched successfully.', $hours);
        } catch (Exception $e) {
            return $this->unifiedResponse(false, 'Failed to fetch working hours.', [], ['error' => $e->getMessage()], 500);
        }
    }

    public function addWorkingHour($doctorId, array $data)
    {
        try {
            $data['doctor_id'] = $doctorId;
            $hour = WorkingHour::create($data);
            return $this->unifiedResponse(true, 'Working hour added successfully.', $hour);
        } catch (Exception $e) {
            return $this->unifiedResponse(false, 'Failed to add working hour.', [], ['error' => $e->getMessage()], 500);
        }
    }

    public function updateWorkingHour($id, array $data)
    {
        try {
            $hour = WorkingHour::findOrFail($id);
            $hour->update($data);
            return $this->unifiedResponse(true, 'Working hour updated successfully.', $hour);
        } catch (Exception $e) {
            return $this->unifiedResponse(false, 'Failed to update working hour.', [], ['error' => $e->getMessage()], 500);
        }
    }

    public function deleteWorkingHour($id)
    {
        try {
            $hour = WorkingHour::findOrFail($id);
            $hour->delete();
            return $this->unifiedResponse(true, 'Working hour deleted successfully.');
        } catch (Exception $e) {
            return $this->unifiedResponse(false, 'Failed to delete working hour.', [], ['error' => $e->getMessage()], 500);
        }
    }

    public function searchDoctors($query)
    {
        try {
            $centerId = Auth::user()->secretaries->first()->center_id;

            $results = Doctor::where('center_id', $centerId)
                ->where(function ($q) use ($query) {
                    $q->whereHas('user', function ($sub) use ($query) {
                        $sub->where('full_name', 'like', "%$query%")
                            ->orWhere('phone', 'like', "%$query%");
                    })
                    ->orWhereHas('user.doctorProfile.specialty', function ($sub) use ($query) {
                        $sub->where('name', 'like', "%$query%");
                    });
                })
                ->with([
                    'user:id,full_name,email,address,phone',
                    'user.doctorProfile:id,user_id,about_me,years_of_experience,specialty_id',
                    'user.doctorProfile.specialty:id,name',
                    'workingHours:id,doctor_id,day_of_week,start_time,end_time',
                    'appointments:id,doctor_id,patient_id'
                ])
                ->get()
                ->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'user_id' => $doctor->user_id,
                        'full_name' => 'Dr. ' . $doctor->user->full_name,
                        'email' => $doctor->user->email,
                        'address' => $doctor->user->address ?? null,
                        'about_me' => $doctor->user->doctorProfile->about_me ?? null,
                        'years_of_experience' => $doctor->user->doctorProfile->years_of_experience ?? null,
                        'specialty' => $doctor->user->doctorProfile->specialty->name ?? null,
                        'working_hours' => $doctor->workingHours->map(function ($hour) {
                            return [
                                'day_of_week' => $hour->day_of_week,
                                'start_time' => $hour->start_time,
                                'end_time' => $hour->end_time,
                            ];
                        }),
                        'total_patients' => $doctor->appointments->unique('patient_id')->count(),
                        'total_appointments' => $doctor->appointments->count(),
                    ];
                });

            if ($results->isEmpty()) {
                return $this->unifiedResponse(false, 'No matching doctors found.', [], [], 404);
            }

            return $this->unifiedResponse(true, 'Search results fetched successfully.', $results);

        } catch (Exception $e) {
            return $this->unifiedResponse(false, 'Failed to search doctors.', [], ['error' => $e->getMessage()], 500);
        }
    }

}
