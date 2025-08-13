<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    protected $fillable = ['name','description','is_active'];

    public function centers()
    {
        return $this->belongsToMany(Center::class, 'center_services')->withTimestamps();
    }
}
