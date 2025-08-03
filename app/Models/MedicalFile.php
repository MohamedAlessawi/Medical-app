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


    public function getUserNameAttribute()
    {
        return $this->user->full_name ?? null;
    }


    public function getTypeTextAttribute()
    {
        return match($this->type) {
            'xray' => 'X-Ray',
            'lab' => 'Lab Test',
            'prescription' => 'Prescription',
            'report' => 'Medical Report',
            'other' => 'Other',
            default => 'Unknown'
        };
    }

    
    public function getUploadDateFormattedAttribute()
    {
        return $this->upload_date ? $this->upload_date->format('Y-m-d H:i') : null;
    }
}
