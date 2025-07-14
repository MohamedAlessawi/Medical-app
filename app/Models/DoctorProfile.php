<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DoctorProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'about_me',
        'years_of_experience',
        'certificate',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
