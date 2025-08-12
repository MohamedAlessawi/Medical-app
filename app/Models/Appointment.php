<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = ['doctor_id', 'patient_id', 'appointment_date', 'appointment_time', 'status', 'booked_by', 'attendance_status', 'notes'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function patient()
    {
        return $this->belongsTo(User::class, 'patient_id');
    }

    public function rating()
    {
        return $this->hasOne(Rating::class, 'appointment_id');
    }


    public function getDoctorNameAttribute()
    {
        return $this->doctor->user->full_name ?? null;
    }


    public function getPatientNameAttribute()
    {
        return $this->user->full_name ?? null;
    }


    public function getCenterNameAttribute()
    {
        return $this->doctor->center->name ?? null;
    }


    public function getAppointmentDateFormattedAttribute()
    {
        return $this->appointment_date ? $this->appointment_date->format('Y-m-d') : null;
    }


    public function getAppointmentTimeFormattedAttribute()
    {
        return $this->appointment_time ? $this->appointment_time : null;
    }


    public function getCreatedAtFormattedAttribute()
    {
        return $this->created_at ? $this->created_at->format('Y-m-d H:i') : null;
    }
}
