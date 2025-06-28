<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = ['doctor_id', 'appointment_date', 'status', 'booked_by', 'attendance_status', 'notes'];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'booked_by');
    }

    public function rating()
    {
        return $this->hasOne(Rating::class, 'appointment_id');
    }
}
