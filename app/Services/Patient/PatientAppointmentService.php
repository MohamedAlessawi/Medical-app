<?php

namespace App\Services\patient;

use App\Models\{Center, Specialty, Doctor, DoctorProfile, WorkingHour, Appointment, AppointmentRequest};
use App\Traits\ApiResponseTrait;
use App\Http\Requests\Patient\AppointmentRequestRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class PatientAppointmentService
{
    use ApiResponseTrait;

    public function getCenters(Request $request)
    {
        $query = Center::where('is_active', true);

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $centers = $query->get()
            ->map(function ($center) {
                return [
                    'id' => $center->id,
                    'name' => $center->name,
                    'address' => $center->location,
                    'phone' => $center->phone ?? null,
                    'doctors_count' => $center->doctors_count,
                ];
            });

        return $this->unifiedResponse(true, 'Centers fetched successfully.', $centers);
    }

    public function getSpecialties(Request $request)
    {
        $query = Specialty::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $specialties = $query->withCount('doctors')->get();

        return $this->unifiedResponse(true, 'Specialties fetched successfully.', $specialties);
    }

    public function getDoctorsByCenterAndSpecialty($centerId, $specialtyId)
    {
        $doctors = Doctor::where('center_id', $centerId)
            ->whereHas('user.doctorProfile', function ($query) use ($specialtyId) {
                $query->where('specialty_id', $specialtyId);
            })
            ->with(['user.doctorProfile.specialty', 'workingHours'])
            ->get()
            ->map(function ($doctor) {
                return [
                    'id' => $doctor->id,
                    'user_id' => $doctor->user_id,
                    'name' => $doctor->user->full_name,
                    'specialty' => $doctor->specialty_name,
                    'experience' => $doctor->experience,
                    'about' => $doctor->about_me,
                    'working_days' => $doctor->workingHours->pluck('day_of_week'),
                ];
            });

        return $this->unifiedResponse(true, 'Doctors fetched successfully.', $doctors);
    }

    public function getDoctorCenters($doctorId)
    {
        $doctor = Doctor::find($doctorId);

        if (!$doctor) {
            return $this->unifiedResponse(false, 'Doctor not found.', [], [], 404);
        }

        $centers = Doctor::where('user_id', $doctor->user_id)
            ->with(['center', 'workingHours'])
            ->get()
            ->map(function ($doctorCenter) {
                return [
                    'center_id' => $doctorCenter->center_id,
                    'center_name' => $doctorCenter->center->name,
                    'center_address' => $doctorCenter->center->address,
                    'working_days' => $doctorCenter->workingHours->pluck('day_of_week'),
                ];
            });

        return $this->unifiedResponse(true, 'Doctor centers fetched successfully.', $centers);
    }

    public function getAvailableSlots($doctorId, $centerId, Request $request)
    {
        
        $date = $request->get('date', Carbon::tomorrow()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);


        if ($selectedDate->isPast()) {
            return $this->unifiedResponse(false, 'Cannot book appointments in the past. Please select a future date.', [], [], 422);
        }

        $dayOfWeek = $selectedDate->format('l');

        $doctor = Doctor::where('id', $doctorId)
            ->where('center_id', $centerId)
            ->first();

        if (!$doctor) {
            return $this->unifiedResponse(false, 'Doctor not found in this center.', [], [], 404);
        }

        $workingHour = WorkingHour::where('doctor_id', $doctorId)
            ->where('day_of_week', $dayOfWeek)
            ->first();

        if (!$workingHour) {
            return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 404);
        }

        $availableSlots = $this->calculateAvailableSlots($doctorId, $centerId, $selectedDate, $workingHour);

        return $this->unifiedResponse(true, 'Available slots fetched successfully.', [
            'date' => $date,
            'day_of_week' => $dayOfWeek,
            'working_hours' => [
                'start_time' => $workingHour->start_time,
                'end_time' => $workingHour->end_time,
            ],
            'available_slots' => $availableSlots,
        ]);
    }

    private function calculateAvailableSlots($doctorId, $centerId, $date, $workingHour)
    {
        $doctor = Doctor::find($doctorId);
        $appointmentDuration = $doctor->appointment_duration;

        $startTime = Carbon::parse($workingHour->start_time);
        $endTime = Carbon::parse($workingHour->end_time);

        $slots = [];
        $currentTime = $startTime->copy();

        while ($currentTime->copy()->addMinutes($appointmentDuration) <= $endTime) {
            $slotTime = $currentTime->format('H:i');

            $existingAppointment = Appointment::where('doctor_id', $doctorId)
                ->whereDate('appointment_date', $date)
                ->whereTime('appointment_date', $slotTime)
                ->where('status', '!=', 'cancelled')
                ->first();

            if (!$existingAppointment) {
                $slots[] = $slotTime;
            }

            $currentTime->addMinutes($appointmentDuration);
        }

        return $slots;
    }

/////////////////////////////////////////////////////////////////////////////

    // public function requestAppointment(AppointmentRequestRequest $request)
    // {
    //     $validated = $request->validated();
    //     $patientId = $request->user()->id;

    //     $doctor = Doctor::where('id', $validated['doctor_id'])
    //         ->where('center_id', $validated['center_id'])
    //         ->first();

    //     if (!$doctor) {
    //         return $this->unifiedResponse(false, 'Doctor not found in this center.', [], [], 404);
    //     }

    //     $appointmentDateTime = Carbon::parse($validated['requested_date'] . ' ' . $validated['requested_time']);

    //     $existingAppointment = Appointment::where('doctor_id', $validated['doctor_id'])
    //         ->whereDate('appointment_date', $validated['requested_date'])
    //         ->whereTime('appointment_date', $validated['requested_time'])
    //         ->where('status', '!=', 'cancelled')
    //         ->first();

    //     if ($existingAppointment) {
    //         return $this->unifiedResponse(false, 'This time slot is already booked.', [], [], 409);
    //     }

    //     $appointmentRequest = AppointmentRequest::create([
    //         'patient_id' => $patientId,
    //         'doctor_id' => $validated['doctor_id'],
    //         'center_id' => $validated['center_id'],
    //         'requested_date' => $appointmentDateTime,
    //         'status' => 'pending',
    //         'notes' => $validated['notes'] ?? null,
    //     ]);

    //     return $this->unifiedResponse(true, 'Appointment request submitted successfully.', $appointmentRequest);
    // }


    public function requestAppointment(AppointmentRequestRequest $request)
    {
        $validated = $request->validated();
        $patientId = $request->user()->id;
    
        $doctor = Doctor::where('id', $validated['doctor_id'])
            ->where('center_id', $validated['center_id'])
            ->first();
    
        if (!$doctor) {
            return $this->unifiedResponse(false, 'Doctor not found in this center.', [], [], 404);
        }
    
        $appointmentDateTime = Carbon::parse($validated['requested_date'] . ' ' . $validated['requested_time']);
    
        if ($appointmentDateTime->isPast()) {
            return $this->unifiedResponse(false, 'Cannot book in the past.', [], [], 422);
        }
    
        $dayOfWeek = $appointmentDateTime->format('l');
    
        $workingHour = WorkingHour::where('doctor_id', $doctor->id)
            ->where('day_of_week', $dayOfWeek)
            ->first();
    
        if (!$workingHour) {
            return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 422);
        }
    
        $requestedTime = Carbon::parse($validated['requested_time']);
        $startTime = Carbon::parse($workingHour->start_time);
        $endTime = Carbon::parse($workingHour->end_time);
    
        if ($requestedTime->lt($startTime) || $requestedTime->gte($endTime)) {
            return $this->unifiedResponse(false, 'Requested time is outside doctor\'s working hours.', [], [], 422);
        }
    
        $availableSlots = $this->calculateAvailableSlots(
            $doctor->id,
            $doctor->center_id,
            $appointmentDateTime->toDateString(),
            $workingHour
        );
    
        if (!in_array($requestedTime->format('H:i'), $availableSlots)) {
            return $this->unifiedResponse(false, 'Requested time is not available.', [], [], 409);
        }
    
        $patientConflict = AppointmentRequest::where('patient_id', $patientId)
            ->where('requested_date', $appointmentDateTime)
            ->whereIn('status', ['pending', 'approved']) 
            ->exists();
    
        if ($patientConflict) {
            return $this->unifiedResponse(false, 'You already have an appointment at this time.', [], [], 409);
        }
    
        $conflictingConfirmed = AppointmentRequest::where('doctor_id', $doctor->id)
            ->where('requested_date', $appointmentDateTime)
            ->where('status', 'approved') 
            ->exists();
    
        if ($conflictingConfirmed) {
            return $this->unifiedResponse(false, 'This time slot is already booked.', [], [], 409);
        }
    
        $appointmentRequest = AppointmentRequest::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctor->id,
            'center_id' => $doctor->center_id,
            'requested_date' => $appointmentDateTime,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);
    
        return $this->unifiedResponse(true, 'Appointment request submitted successfully.', $appointmentRequest);
    }
    




    /////////////////////////////////////////////////////////////////////////////////////

    public function getAppointmentRequests(Request $request)
    {
        $patientId = $request->user()->id;

        $requests = AppointmentRequest::where('patient_id', $patientId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => $request->id,
                    'doctor_name' => $request->doctor_name,
                    'center_name' => $request->center_name,
                    'requested_date' => $request->requested_date_formatted,
                    'requested_time' => $request->requested_time_formatted,
                    'status' => $request->status,
                    'notes' => $request->notes,
                    'created_at' => $request->created_at_formatted,
                ];
            });

        return $this->unifiedResponse(true, 'Appointment requests fetched successfully.', $requests);
    }






///////////////////////////////////////////////////////////////



public function getCenterDetails($centerId)
{
    $center = Center::with([
        'doctors.user.doctorProfile.specialty',
        'doctors.workingHours'
    ])->find($centerId);

    if (!$center) {
        return $this->unifiedResponse(false, 'Center not found.', [], [], 404);
    }

    $specialties = $center->doctors
        ->groupBy(function ($doctor) {
            return $doctor->specialty ? $doctor->specialty->id : null;
        })
        ->filter(function ($group, $specialtyId) {
            return !is_null($specialtyId);
        })
        ->map(function ($doctors, $specialtyId) {
            $specialtyName = optional($doctors->first()->specialty)->name;

            return [
                'id' => $specialtyId,
                'name' => $specialtyName,
                'doctors' => $doctors->map(function ($doctor) {
                    return [
                        'id' => $doctor->id,
                        'name' => $doctor->user->full_name,
                        'specialty' => $doctor->specialty_name,
                        'experience' => $doctor->experience,
                        'about' => $doctor->about_me,
                        'working_hours' => $doctor->workingHours->map(function ($wh) {
                            return [
                                'day_of_week' => $wh->day_of_week,
                                'start_time' => $wh->start_time,
                                'end_time' => $wh->end_time,
                            ];
                        })->values(),
                    ];
                })->values()
            ];
        })->values();

    $data = [
        'id' => $center->id,
        'name' => $center->name,
        'address' => $center->location,
        'phone' => $center->phone,
        'specialties' => $specialties,
    ];

    return $this->unifiedResponse(true, 'Center details fetched successfully.', $data);
}




/////////////////////////////////////////////////////////////////////



public function getAvailableSlotsBySpecialty($centerId, $specialtyId, $doctorId, Request $request)
{
    $date = $request->get('date', Carbon::tomorrow()->format('Y-m-d'));
    $selectedDate = Carbon::parse($date);

    if ($selectedDate->isPast()) {
        return $this->unifiedResponse(false, 'Cannot book appointments in the past. Please select a future date.', [], [], 422);
    }

    $dayOfWeek = $selectedDate->format('l');

    $doctor = Doctor::where('id', $doctorId)
        ->where('center_id', $centerId)
        ->whereHas('user.doctorProfile', function ($query) use ($specialtyId) {
            $query->where('specialty_id', $specialtyId);
        })
        ->first();

    if (!$doctor) {
        return $this->unifiedResponse(false, 'Doctor not found in this center or specialty.', [], [], 404);
    }

    $workingHour = WorkingHour::where('doctor_id', $doctorId)
        ->where('day_of_week', $dayOfWeek)
        ->first();

    if (!$workingHour) {
        return $this->unifiedResponse(false, 'Doctor does not work on this day.', [], [], 404);
    }

    $availableSlots = $this->calculateAvailableSlots($doctorId, $centerId, $selectedDate, $workingHour);

    return $this->unifiedResponse(true, 'Available slots fetched successfully.', [
        'date' => $date,
        'day_of_week' => $dayOfWeek,
        'working_hours' => [
            'start_time' => $workingHour->start_time,
            'end_time' => $workingHour->end_time,
        ],
        'available_slots' => $availableSlots,
    ]);
}

//////////////////////////////////////////////////////////////////////////////


public function getDoctorProfile($doctorId)
{
    $doctor = Doctor::with(['user.doctorProfile.specialty', 'workingHours', 'center'])
        ->find($doctorId);

    if (!$doctor) {
        return $this->unifiedResponse(false, 'Doctor not found.', [], [], 404);
    }

    $centers = Doctor::where('user_id', $doctor->user_id)
        ->with(['center', 'workingHours'])
        ->get()
        ->map(function ($doctorCenter) {
            return [
                'center_id' => $doctorCenter->center_id,
                'center_name' => $doctorCenter->center->name,
                'center_address' => $doctorCenter->center->location,
                'working_hours' => $doctorCenter->workingHours->map(function ($wh) {
                    return [
                        'day_of_week' => $wh->day_of_week,
                        'start_time' => $wh->start_time,
                        'end_time' => $wh->end_time,
                    ];
                }),
            ];
        });

    $data = [
        'id' => $doctor->id,
        'name' => $doctor->user->full_name,
        'specialty' => $doctor->doctorProfile->specialty->name ?? null,
        'experience' => $doctor->years_of_experience,
        'about' => $doctor->doctorProfile->about_me,
        'certificate' => $doctor->doctorProfile->certificate,
        'centers' => $centers,
    ];

    return $this->unifiedResponse(true, 'Doctor profile fetched successfully.', $data);
}

////////////////////////////////////////////////////////////////////////


public function getCentersAndDoctorsBySpecialty($specialtyId)
{
    $specialty = Specialty::with([
        'doctors.center',
        'doctors.user.doctorProfile',
        'doctors.workingHours' 
    ])->find($specialtyId);

    if (!$specialty) {
        return $this->unifiedResponse(false, 'Specialty not found.', [], [], 404);
    }

    $centers = $specialty->doctors
        ->groupBy('center_id')
        ->map(function ($doctors, $centerId) {
            $center = $doctors->first()->center;
            return [
                'center_id' => $center->id,
                'center_name' => $center->name,
                'center_address' => $center->location,
                'doctors' => $doctors->map(function ($doctor) {
                    return [
                        'doctor_id'   => $doctor->id,
                        'doctor_name' => $doctor->user->full_name,
                        'experience'  => $doctor->experience,
                        'about'       => $doctor->about_me,
                        'working_hours' => $doctor->workingHours->map(function ($wh) {
                            return [
                                'day_of_week' => $wh->day_of_week,
                                'start_time'  => $wh->start_time,
                                'end_time'    => $wh->end_time,
                            ];
                        })->values()
                    ];
                })->values()
            ];
        })
        ->values();

    $data = [
        'specialty_id'   => $specialty->id,
        'specialty_name' => $specialty->name,
        'centers'        => $centers,
    ];

    return $this->unifiedResponse(true, 'Centers and doctors fetched successfully.', $data);
}

public function cancelPendingAppointmentRequest($id)
{
    $user = Auth::user();

    $appointmentRequest = AppointmentRequest::where('id', $id)
        ->where('patient_id', $user->id) 
        ->where('status', 'pending') 
        ->whereDate('requested_date', '>', Carbon::tomorrow()) 
        ->first();

    if (!$appointmentRequest) {
        return $this->unifiedResponse(
            false,
            'Appointment request cannot be cancelled. Either it is already processed or it is too close to the appointment date.',
            [],
            [],
            422
        );
    }

    $appointmentRequest->update([
        'status' => 'rejected'
    ]);

    return $this->unifiedResponse(true, 'Appointment request cancelled successfully.', $appointmentRequest);
}


public function getPastAppointmentsForPatient()
{
    $patientId = Auth::id(); 

    $appointments = Appointment::with(['doctor.user:id,full_name,email,phone'])
        ->where('booked_by', $patientId)
        ->where('status', 'confirmed')
        ->where('appointment_date', '<', now())
        ->orderByDesc('appointment_date')
        ->get();

    return $this->unifiedResponse(true, 'Past appointments fetched successfully.', $appointments);
}

}
