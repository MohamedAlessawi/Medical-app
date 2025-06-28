<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Center extends Model
{
    use HasFactory;
     protected $fillable = ['name', 'location', 'rating'];

    protected $casts = [
        'rating' => 'decimal:3',
    ];

    public function userCenters()
    {
        return $this->hasMany(UserCenter::class, 'center_id');
    }

    public function adminCenters()
    {
        return $this->hasMany(AdminCenter::class, 'center_id');
    }

    public function secretaries()
    {
        return $this->hasMany(Secretary::class, 'center_id');
    }

    public function reports()
    {
        return $this->hasMany(Report::class, 'center_id');
    }
}
