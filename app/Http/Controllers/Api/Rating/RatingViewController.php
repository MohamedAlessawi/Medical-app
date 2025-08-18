<?php

namespace App\Http\Controllers\Api\Rating;

use App\Http\Controllers\Controller;
use App\Services\Rating\RatingViewService;

class RatingViewController extends Controller
{
    public function __construct(private RatingViewService $service) {}

    public function getDoctorRatings($userId)
    {
       return $this->service->getDoctorRatings((int) $userId);
    }

    public function getCenterRatings($centerId)
    {
        return $this->service->getCenterRatings((int) $centerId);
    }
}
