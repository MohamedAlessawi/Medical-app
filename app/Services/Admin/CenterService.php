<?php

namespace App\Services\Admin;

use App\Models\Center;
use App\Models\CenterWorkingHour;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CenterService
{
    use ApiResponseTrait;

    private function myCenter(): Center
    {
        $centerId = DB::table('admin_centers')->where('user_id', Auth::id())->value('center_id');
        return Center::with('workingHours')->findOrFail($centerId);
    }

    public function show()
    {
        return $this->unifiedResponse(true, 'Center fetched.', $this->myCenter());
    }

    public function update(array $data)
    {
        $center = $this->myCenter();
        $center->fill($data);
        $center->save();
        return $this->unifiedResponse(true, 'Center updated.', $center);
    }

    public function uploadImage($file)
    {
        $center = $this->myCenter();
        $path = $file->store('centers', 'public');
        $center->image = $path;
        $center->save();

        $payload = $center->toArray();
        $payload['image_url'] = asset('storage/'.$center->image);

        return $this->unifiedResponse(true, 'Image uploaded.', $payload);
    }

    public function workingHoursIndex()
    {
        $center = $this->myCenter();
        return $this->unifiedResponse(true, 'Center working hours fetched.', $center->workingHours()->orderByRaw("FIELD(day_of_week,'Saturday','Sunday','Monday','Tuesday','Wednesday','Thursday','Friday')")->get());
    }

    public function workingHoursBulkUpdate(array $items)
    {
        $center = $this->myCenter();

        $existing = $center->workingHours()->get()->keyBy('day_of_week');

        foreach ($items as $row) {
            $wh = $existing->get($row['day_of_week']);
            if (!$wh) {
                $wh = new CenterWorkingHour(['center_id' => $center->id, 'day_of_week' => $row['day_of_week']]);
            }
            $wh->is_open    = (bool) $row['is_open'];
            $wh->open_time  = $row['is_open'] ? $row['open_time'] : null;
            $wh->close_time = $row['is_open'] ? $row['close_time'] : null;
            $wh->center_id  = $center->id;
            $wh->save();
        }

        return $this->unifiedResponse(true, 'Center working hours updated.', $center->workingHours);
    }
}
