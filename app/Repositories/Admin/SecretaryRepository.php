<?php

namespace App\Repositories\Admin;

use App\Models\Secretary;

class SecretaryRepository
{
    public function listByCenter(int $centerId)
    {
        return Secretary::with('user')->where('center_id', $centerId)->get();
    }

    public function toggleStatus(int $userId, int $centerId, bool $isActive): void
    {
        Secretary::where('user_id', $userId)
                 ->where('center_id', $centerId)
                 ->update(['is_active' => $isActive]);
    }

    public function remove(int $userId, int $centerId): void
    {
        Secretary::where('user_id', $userId)
                 ->where('center_id', $centerId)
                 ->delete();
    }
}
