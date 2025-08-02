<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AppointmentRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'center_id',
        'requested_date',
        'status',
        'notes',
    ];

    protected $casts = [
        'requested_date' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function center()
    {
        return $this->belongsTo(Center::class);
    }


    public function getPatientNameAttribute()
    {
        return $this->patient->full_name ?? null;
    }


    public function getDoctorNameAttribute()
    {
        return $this->doctor->user->full_name ?? null;
    }


    public function getCenterNameAttribute()
    {
        return $this->center->name ?? null;
    }


    public function getSpecialtyNameAttribute()
    {
        return $this->doctor->user->doctorProfile->specialty->name ?? null;
    }


    public function getRequestedDateFormattedAttribute()
    {
        return $this->requested_date ? $this->requested_date->format('Y-m-d') : null;
    }


    public function getRequestedTimeFormattedAttribute()
    {
        return $this->requested_date ? $this->requested_date->format('H:i') : null;
    }


    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i') : null;
    }
}
