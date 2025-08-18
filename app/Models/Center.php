<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    use HasFactory;
     protected $fillable = [
        'name',
        'location',
        'rating',
        'is_active',
        'phone',
        'image'
    ];

    protected $casts = [
        'rating' => 'decimal:3',
        'is_active' => 'boolean'
    ];

    public function userCenters()
    {
        return $this->hasMany(UserCenter::class, 'center_id');
    }

    public function adminCenters()
    {
        return $this->hasMany(AdminCenter::class, 'center_id');
    }

    public function secretaries()
    {
        return $this->hasMany(Secretary::class, 'center_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'center_id');
    }

    public function doctors()
    {
        return $this->hasMany(Doctor::class, 'center_id');
    }

    public function appointmentRequests()
    {
        return $this->hasMany(AppointmentRequest::class, 'center_id');
    }


    public function getDoctorsCountAttribute()
    {
        return $this->doctors()->count();
    }


    public function getSpecialtiesCountAttribute()
    {
        return $this->doctors()
            ->whereHas('user.doctorProfile.specialty')
            ->get()
            ->pluck('user.doctorProfile.specialty_id')
            ->unique()
            ->count();
    }

    public function workingHours()
    {
        return $this->hasMany(\App\Models\CenterWorkingHour::class);
    }
    public function ratings()
{
    return $this->morphMany(\App\Models\Rating::class, 'rateable');
}

public function getAverageRatingAttribute()
{
    return round($this->ratings()->avg('score') ?? 0, 1);
}


}
