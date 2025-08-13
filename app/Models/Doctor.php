<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Doctor extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'center_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function doctorProfile()
    {
        return $this->hasOne(DoctorProfile::class, 'user_id', 'user_id');
    }

    public function specialty()
    {
        return $this->hasOneThrough(Specialty::class, DoctorProfile::class, 'user_id', 'id', 'user_id', 'specialty_id');
    }

    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class, 'doctor_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function appointmentRequests()
    {
        return $this->hasMany(AppointmentRequest::class, 'doctor_id');
    }


    public function getSpecialtyAttribute()
    {
        return $this->user->doctorProfile->specialty ?? null;
    }


    public function getSpecialtyNameAttribute()
    {
        return $this->user->doctorProfile->specialty->name ?? null;
    }


    public function getExperienceAttribute()
    {
        return $this->user->doctorProfile->years_of_experience ?? null;
    }


    public function getAboutMeAttribute()
    {
        return $this->user->doctorProfile->about_me ?? '';
    }


    public function getAppointmentDurationAttribute()
    {
        return $this->user->doctorProfile->appointment_duration ?? 30;
    }
}
