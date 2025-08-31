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
        return $this->hasManyThrough(
            Doctor::class,
            DoctorProfile::class, 
            'specialty_id',       
            'user_id',            
            'id',                 
            'user_id'             
        );
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
