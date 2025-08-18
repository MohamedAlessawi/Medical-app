<?php

namespace App\Services\Rating;

use App\Repositories\RatingRepository;
use App\Models\{Doctor, Center, DoctorProfile};
use App\Traits\ApiResponseTrait;

class RatingViewService
{
    use ApiResponseTrait;

    public function __construct(private RatingRepository $ratings) {}

public function getDoctorRatings(int $userId)
{
    $user = \App\Models\User::find($userId);

    if (!$user) {
        return $this->unifiedResponse(false, 'User not found.', [], [], 404);
    }

    $profile = $user->doctorProfile ?? null;
    if (!$profile) {
        return $this->unifiedResponse(false, 'Doctor profile not found for this user.', [], [], 404);
    }

    $summary    = $this->ratings->getSummaryFor(DoctorProfile::class, $profile->id);
    $allRatings = $this->ratings->getRatingsFor(DoctorProfile::class, $profile->id);

    return $this->unifiedResponse(true, 'Doctor ratings fetched successfully.', [
        'doctor' => [
            'user_id' => $user->id,
            'name'    => $user->full_name,
        ],
        'summary' => $summary,
        'ratings' => $allRatings,
    ]);
}

    public function getCenterRatings(int $centerId)
    {
        $center = Center::find($centerId);

        if (!$center) {
            return $this->unifiedResponse(false, 'Center not found.', [], [], 404);
        }

        $summary = $this->ratings->getSummaryFor(Center::class, $center->id);
        $allRatings = $this->ratings->getRatingsFor(Center::class, $center->id);

        return $this->unifiedResponse(true, 'Center ratings fetched successfully.', [
            'center' => [
                'id'   => $center->id,
                'name' => $center->name,
            ],
            'summary' => $summary,
            'ratings' => $allRatings,
        ]);
    }
}
