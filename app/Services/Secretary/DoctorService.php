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
                ->with('user.doctorProfile')
                ->get();

            return $this->unifiedResponse(true, 'Doctors retrieved successfully.', $doctors);
        } catch (Exception $e) {
            Log::error('Failed to fetch doctors: ' . $e->getMessage());
            return $this->unifiedResponse(false, 'Failed to fetch doctors.', [], ['error' => $e->getMessage()], 500);
        }
    }

    public function getDoctorDetails($id)
    {
        try {
            $doctor = Doctor::with('user.doctorProfile')->findOrFail($id);
            return $this->unifiedResponse(true, 'Doctor details fetched.', $doctor);
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
        $centerId = Auth::user()->secretaries->first()->center_id;

        $results = Doctor::where('center_id', $centerId)
            ->where(function ($q) use ($query) {
                $q->whereHas('user', function ($sub) use ($query) {
                    $sub->where('full_name', 'like', "%$query%")
                        ->orWhere('phone', 'like', "%$query%");
                })
                ->orWhereHas('user.doctorProfile', function ($sub) use ($query) {
                    $sub->where('specialization', 'like', "%$query%");
                });
            })
            ->with(['user:id,full_name,phone,email', 'user.doctorProfile:id,user_id,specialization'])
            ->get();

        if ($results->isEmpty()) {
            return $this->unifiedResponse(false, 'No matching doctors found.', [], [], 404);
        }

        return $this->unifiedResponse(true, 'Search results', $results);
    }


}
