<?php
// app/Repositories/RatingRepository.php
namespace App\Repositories;

use App\Models\Rating;

class RatingRepository
{
    public function create(array $data): Rating
    {
        return Rating::create($data);
    }

    public function existsForTarget(int $appointmentId, int $userId, string $type, int $id): bool
    {
        return Rating::where([
            'appointment_id' => $appointmentId,
            'user_id'        => $userId,
            'rateable_type'  => $type,
            'rateable_id'    => $id,
        ])->exists();
    }

    public function doctorAverage(int $doctorProfileId): ?float
    {
        return Rating::where([
            'rateable_type' => \App\Models\DoctorProfile::class,
            'rateable_id'   => $doctorProfileId,
        ])->avg('score');
    }

    public function centerAverage(int $centerId): ?float
    {
        return Rating::where([
            'rateable_type' => \App\Models\Center::class,
            'rateable_id'   => $centerId,
        ])->avg('score');
    }
     public function getSummaryFor(string $type, int $id): array
    {
        $query = Rating::where('rateable_type', $type)
            ->where('rateable_id', $id);

        return [
            'average' => round($query->avg('score') ?? 0, 1),
            'count'   => $query->count(),
        ];
    }

    public function getRatingsFor(string $type, int $id)
    {
        return Rating::where('rateable_type', $type)
            ->where('rateable_id', $id)
            ->orderByDesc('created_at')
            ->get(['id', 'score', 'comment', 'created_at']);
    }

}

