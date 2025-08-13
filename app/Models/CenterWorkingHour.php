<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterWorkingHour extends Model
{
    protected $fillable = [
        'center_id','day_of_week','is_open','open_time','close_time'
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function center()
    {
        return $this->belongsTo(Center::class);
    }
}
