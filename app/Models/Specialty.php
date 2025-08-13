<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function doctors()
    {
        return $this->hasMany(DoctorProfile::class, 'specialty_id');
    }



    public function getDoctorsCountAttribute()
    {
        return $this->doctors()->count();
    }


    public function getActiveDoctorsAttribute()
    {
        return $this->doctors()->where('status', 'approved')->get();
    }
}
