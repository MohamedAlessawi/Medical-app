<?php

namespace App\Services\Doctor;

use App\Models\Doctor;
use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DoctorCenterService
{
    use ApiResponseTrait;

    public function getDoctorCenters()
    {
        $user = Auth::user();

        // جبنا مراكز الدكتور
        $rows = Doctor::with('center:id,name,location,phone,image')
            ->where('user_id', $user->id)
            ->get();

        // IDs المراكز
        $centerIds = $rows->pluck('center.id')->filter()->unique()->values();

        // متوسط التقييم لكل مركز (0..5) بتجميعة وحدة
        $ratingByCenter = DB::table('ratings')
            ->select('rateable_id', DB::raw('ROUND(AVG(score), 1) as avg_score'))
            ->where('rateable_type', \App\Models\Center::class)
            ->whereIn('rateable_id', $centerIds)
            ->groupBy('rateable_id')
            ->get()
            ->keyBy('rateable_id');

        // تجهيز الـ payload
        $data = $rows->map(function ($doctor) use ($ratingByCenter) {
            $c = $doctor->center;
            $avg = optional($ratingByCenter->get($c->id))->avg_score ?? 0.0;

            $imageUrl = $c->image
                ? Storage::disk('public')->url(ltrim($c->image, '/'))
                : null;


            return [
                'center_id'       => $c->id,
                'center_name'     => $c->name,
                'location'         => $c->location,
                'phone'           => $c->phone,
                'image_url'       => $imageUrl,
                'rating_average'  => (float) $avg,  // 0..5
            ];
        });

        return $this->unifiedResponse(true, 'Centers fetched successfully.', $data);
    }

    // public function getDoctorCenters()
    // {
    //     $user = Auth::user();

    //     $doctorCenters = Doctor::with('center')
    //         ->where('user_id', $user->id)
    //         ->get()
    //         ->map(function ($doctor) {
    //             return [
    //                 'center_id' => $doctor->center->id,
    //                 'center_name' => $doctor->center->name,
    //                 'address' => $doctor->center->address,
    //                 'phone' => $doctor->center->phone,
    //             ];
    //         });

    //     return $this->unifiedResponse(true, 'Centers fetched successfully.', $doctorCenters);
    // }
}
