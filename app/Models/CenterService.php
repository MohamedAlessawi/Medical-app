<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CenterService extends Model
{
    protected $table = 'center_services';
    protected $fillable = ['center_id','service_id'];

    public function center()  { return $this->belongsTo(Center::class); }
    public function service() { return $this->belongsTo(Service::class); }
}
