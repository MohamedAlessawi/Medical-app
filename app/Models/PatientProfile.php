<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    protected $fillable = ['user_id', 'gender', 'blood_type', 'birth_date', 'medical_notes'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

