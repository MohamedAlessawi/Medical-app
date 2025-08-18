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

    public function listByCenterFormatted(int $centerId)
    {
        // نحمل علاقة user ثم نكوّن المخرجات المطلوبة
        $rows = Secretary::with('user')
            ->where('center_id', $centerId)
            ->get();

        $mapShift = ['morning' => 'الصباح', 'evening' => 'المساء', 'night' => 'الليل'];

        return $rows->map(function ($s) use ($mapShift) {
            return [
                'user_id'   => $s->user_id,
                'full_name' => $s->user?->full_name,
                'email'     => $s->user?->email,
                'phone'     => $s->user?->phone,
                'shift'     => $s->shift,
                // 'shift_label' => $mapShift[$s->shift] ?? $s->shift, // للواجهة
                'is_active' => (bool) $s->is_active,
                // 'status_label' => $s->is_active ? 'نشط' : 'غير نشط',
            ];
        });
    }

    public function updateDetails(int $userId, int $centerId, array $data): array
    {
        // حدّث بيانات المستخدم (إن وُجدت)
        $userUpdates = array_intersect_key($data, array_flip(['full_name','email','phone']));
        if (!empty($userUpdates)) {
            \App\Models\User::where('id', $userId)->update($userUpdates);
        }

        // حدّث بيانات السكرتير (shift / is_active)
        $secUpdates = array_intersect_key($data, array_flip(['shift','is_active']));
        if (!empty($secUpdates)) {
            Secretary::where('user_id', $userId)
                ->where('center_id', $centerId)
                ->update($secUpdates);
        }

        // رجّع الصفّ بعد التحديث بشكل مُهيأ للواجهة
        return $this->listByCenterFormatted($centerId)
            ->firstWhere('user_id', $userId) ?? [];
    }

}
