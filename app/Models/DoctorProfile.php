<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'specialty_id',
        'about_me',
        'years_of_experience',
        'certificate',
        'status',
        'appointment_duration'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function specialty()
    {
        return $this->belongsTo(Specialty::class);
    }


    public function getSpecialtyNameAttribute()
    {
        return $this->specialty->name ?? null;
    }


    public function getStatusTextAttribute()
    {
        return match($this->status) {
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
            default => 'Unknown'
        };
    }


    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }
    public function ratings()
{
    return $this->morphMany(\App\Models\Rating::class, 'rateable');
}

}
