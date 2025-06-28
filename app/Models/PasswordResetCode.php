<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetCode extends Model
{
    use HasFactory;
    protected $fillable = [
        'email',
        'code',
        'created_at',
    ];
    protected $dates = ['created_at'];


    public $timestamps = false;
}
