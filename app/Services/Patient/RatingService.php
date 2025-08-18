<?php
namespace App\Services\Patient;

use App\Models\{Appointment, DoctorProfile, Center};
use App\Repositories\RatingRepository;
use App\Traits\ApiResponseTrait;

class RatingService
{
    use ApiResponseTrait;

    public function __construct(private RatingRepository $ratings) {}

    public function rateDoctor(int $appointmentId, float $score, ?string $comment = null)
    {
        $userId = auth()->id();

        $appointment = Appointment::with(['doctor.user.doctorProfile'])
            ->where('id', $appointmentId)
            ->where('status', 'confirmed')
            ->first();

        if (!$appointment) {
            return $this->unifiedResponse(false,'Appointment not found or not confirmed.',[],[],404);
        }

        // المريض صاحب الموعد فقط
        if (!in_array($userId, array_filter([$appointment->booked_by, $appointment->patient_id]))) {
            return $this->unifiedResponse(false,'Unauthorized to rate this appointment.',[],[],403);
        }

        // الحضور مؤكّد + الموعد مضى
        if ($appointment->attendance_status !== 'present' || now()->lt($appointment->appointment_date)) {
            return $this->unifiedResponse(false,'You can rate only after attending the appointment.',[],[],422);
        }

        $profile = optional($appointment->doctor->user)->doctorProfile;
        if (!$profile) {
            return $this->unifiedResponse(false,'Doctor profile not found.',[],[],404);
        }

        if ($this->ratings->existsForTarget($appointment->id, $userId, DoctorProfile::class, $profile->id)) {
            return $this->unifiedResponse(false,'You already rated this doctor for this appointment.',[],[],409);
        }

        // تحقق من الدرجة 0..5 بفواصل 0.5/0.1 حسب اختيارك
        if ($score < 0 || $score > 5) {
            return $this->unifiedResponse(false,'Score must be between 0 and 5.',[],[],422);
        }

        $rating = $this->ratings->create([
            'appointment_id' => $appointment->id,
            'user_id'        => $userId,
            'rateable_type'  => DoctorProfile::class,
            'rateable_id'    => $profile->id,
            'score'          => round($score,1),
            'comment'        => $comment,
        ]);

        return $this->unifiedResponse(true,'Doctor rated successfully.',$rating);
    }

    /** قيّم المركز */
    public function rateCenter(int $appointmentId, float $score, ?string $comment = null)
    {
        $userId = auth()->id();

        $appointment = Appointment::with(['doctor.center'])
            ->where('id', $appointmentId)
            ->where('status', 'confirmed')
            ->first();

        if (!$appointment) {
            return $this->unifiedResponse(false,'Appointment not found or not confirmed.',[],[],404);
        }

        if (!in_array($userId, array_filter([$appointment->booked_by, $appointment->patient_id]))) {
            return $this->unifiedResponse(false,'Unauthorized to rate this appointment.',[],[],403);
        }

        if ($appointment->attendance_status !== 'present' || now()->lt($appointment->appointment_date)) {
            return $this->unifiedResponse(false,'You can rate only after attending the appointment.',[],[],422);
        }

        $center = $appointment->doctor->center ?? null;
        if (!$center) {
            return $this->unifiedResponse(false,'Center not found.',[],[],404);
        }

        if ($this->ratings->existsForTarget($appointment->id, $userId, Center::class, $center->id)) {
            return $this->unifiedResponse(false,'You already rated this center for this appointment.',[],[],409);
        }

        if ($score < 0 || $score > 5) {
            return $this->unifiedResponse(false,'Score must be between 0 and 5.',[],[],422);
        }

        $rating = $this->ratings->create([
            'appointment_id' => $appointment->id,
            'user_id'        => $userId,
            'rateable_type'  => Center::class,
            'rateable_id'    => $center->id,
            'score'          => round($score,1),
            'comment'        => $comment,
        ]);

        return $this->unifiedResponse(true,'Center rated successfully.',$rating);
    }
}
