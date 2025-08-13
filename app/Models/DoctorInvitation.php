<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorInvitation extends Model
{
    protected $fillable = [
        'center_id','doctor_user_id','invited_by','status','message','expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function center()     { return $this->belongsTo(Center::class); }
    public function doctorUser() { return $this->belongsTo(User::class, 'doctor_user_id'); }
    public function inviter()    { return $this->belongsTo(User::class, 'invited_by'); }
}

