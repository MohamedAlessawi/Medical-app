<?php

namespace App\Repositories\Admin;

use App\Models\Doctor;

class DoctorRepository
{
    public function listByCenter(int $centerId)
    {
        return Doctor::with(['user','center'])
            ->where('center_id', $centerId)
            ->get();
    }

    public function linkDoctorToCenter(int $doctorUserId, int $centerId): Doctor
    {
        $existing = Doctor::where('user_id', $doctorUserId)
                          ->where('center_id', $centerId)
                          ->first();
        if ($existing) {
            $existing->is_active = true;
            $existing->save();
            return $existing;
        }
        return Doctor::create([
            'user_id'   => $doctorUserId,
            'center_id' => $centerId,
            'is_active' => true
        ]);
    }

    public function toggleStatus(int $doctorId, bool $isActive): Doctor
    {
        $doc = Doctor::findOrFail($doctorId);
        $doc->is_active = $isActive;
        $doc->save();
        return $doc;
    }

    public function removeFromCenter(int $doctorId): void
    {
        Doctor::where('id', $doctorId)->delete();
    }


    // public function listDoctorsNotInCenter(int $centerId, ?string $search = null)
    // {
    //     $q = \DB::table('users')
    //         ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
    //         ->join('roles', 'roles.id', '=', 'user_roles.role_id')
    //         ->where('roles.name', 'doctor')
    //         ->whereNotExists(function ($sub) use ($centerId) {
    //             $sub->select(\DB::raw(1))
    //                 ->from('doctors')
    //                 ->whereColumn('doctors.user_id', 'users.id')
    //                 ->where('doctors.center_id', $centerId);
    //         });

    //     if ($search) {
    //         $q->where(function ($w) use ($search) {
    //             $w->where('users.full_name', 'like', "%{$search}%")
    //             ->orWhere('users.email', 'like', "%{$search}%")
    //             ->orWhere('users.phone', 'like', "%{$search}%");
    //         });
    //     }

    //     $q->select([
    //         'users.id as user_id',
    //         'users.full_name',
    //         'users.email',
    //         'users.phone',
    //         \DB::raw("(select count(*) from doctors d where d.user_id = users.id) as centers_count")
    //     ])->orderBy('users.full_name');

    //     return $q->get();
    // }

    public function listDoctorsNotInCenter(int $centerId, ?string $search = null)
    {
        $q = \DB::table('users')
            ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->where('roles.name', 'doctor')
            ->whereNotExists(function ($sub) use ($centerId) {
                $sub->select(\DB::raw(1))
                    ->from('doctors')
                    ->whereColumn('doctors.user_id', 'users.id')
                    ->where('doctors.center_id', $centerId);
            });

        if ($search !== null && $search !== '') {
            $q->where(function ($w) use ($search) {
                $w->where('users.full_name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%")
                ->orWhere('users.phone', 'like', "%{$search}%");
            });
        }

        $rows = $q->select([
                'users.id as user_id',
                'users.full_name',
                'users.email',
                'users.phone',
                \DB::raw("(select count(*) from doctors d where d.user_id = users.id) as centers_count"),
            ])
            ->orderBy('users.full_name')
            ->get();

        if ($rows->isEmpty()) return $rows;

        // IDs
        $ids = $rows->pluck('user_id')->all();

        // Profiles keyed by user_id
        $profiles = \App\Models\DoctorProfile::whereIn('user_id', $ids)->get()->keyBy('user_id');

        // Pending invites for THIS center keyed by doctor_user_id
        $pendingInvites = \App\Models\DoctorInvitation::where('center_id', $centerId)
            ->where('status', 'pending')
            ->whereIn('doctor_user_id', $ids)
            ->get()
            ->keyBy('doctor_user_id');

        // Build final payload
        return $rows->map(function ($r) use ($profiles, $pendingInvites) {
            $profile = $profiles->get($r->user_id);
            $hasPending = $pendingInvites->has($r->user_id);

            return [
                'user_id'           => (int) $r->user_id,
                'full_name'         => $r->full_name,
                'email'             => $r->email,
                'phone'             => $r->phone,
                'centers_count'     => (int) $r->centers_count,
                'invitation_status' => $hasPending ? 'pending' : null,
                'profile'           => $profile ? $profile->toArray() : null,
            ];
        });
    }


}
