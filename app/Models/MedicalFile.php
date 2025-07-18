<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalFile extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'file_url', 'type', 'upload_date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
