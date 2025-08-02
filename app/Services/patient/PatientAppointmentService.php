<?php

namespace App\Services\Patient;

use App\Models\{Center, Specialty, Doctor, DoctorProfile, WorkingHour, Appointment, AppointmentRequest};
use App\Traits\ApiResponseTrait;
use App\Http\Requests\Patient\AppointmentRequestRequest;
use Carbon\Carbon;
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
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $selectedDate = Carbon::parse($date);
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

        $existingAppointment = Appointment::where('doctor_id', $validated['doctor_id'])
            ->whereDate('appointment_date', $validated['requested_date'])
            ->whereTime('appointment_date', $validated['requested_time'])
            ->where('status', '!=', 'cancelled')
            ->first();

        if ($existingAppointment) {
            return $this->unifiedResponse(false, 'This time slot is already booked.', [], [], 409);
        }


        $appointmentRequest = AppointmentRequest::create([
            'patient_id' => $patientId,
            'doctor_id' => $validated['doctor_id'],
            'center_id' => $validated['center_id'],
            'requested_date' => $appointmentDateTime,
            'status' => 'pending',
            'notes' => $validated['notes'] ?? null,
        ]);

        return $this->unifiedResponse(true, 'Appointment request submitted successfully.', $appointmentRequest);
    }


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
}
