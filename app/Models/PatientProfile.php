<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientProfile extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id', 'condition', 'last_visit', 'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}
