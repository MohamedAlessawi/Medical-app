<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
     protected $fillable = [
        'appointment_id','user_id','rateable_type','rateable_id','score','comment'
    ];

    public function rateable()     {
         return $this->morphTo(); }
    public function rater()        {
        return $this->belongsTo(User::class,'user_id'); }
    public function appointment()  {
        return $this->belongsTo(Appointment::class); }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
