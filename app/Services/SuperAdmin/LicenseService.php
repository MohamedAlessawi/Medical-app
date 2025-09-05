<?php

namespace App\Services\SuperAdmin;

use App\Models\License;
use App\Traits\ApiResponseTrait;


class LicenseService
{
    use ApiResponseTrait;

    public function listLicenses()
    {
        $licenses = License::with(['center:id,name'])
            ->latest()
            ->get()
            ->map(function ($l) {
                $fileUrl = $l->file_path
                    ? asset('storage/' . ltrim($l->file_path, '/'))
                    : null;

                return [
                    'id'         => $l->id,
                    'user_id'    => $l->user_id,
                    'center_id'  => $l->center_id,
                    'status'     => $l->status,
                    'issued_by'  => $l->issued_by,
                    'issue_date' => optional($l->issue_date)->toDateString() ?? (string)$l->issue_date,
                    'file_path'  => $fileUrl,

                    'center' => [
                        'id'   => $l->center?->id,
                        'name' => $l->center?->name,
                    ],

                    'user' => [
                        'id'   => $l->user?->id,
                        'name' => $l->user?->full_name,
                    ],

                    'created_at' => optional($l->created_at)->toDateTimeString(),
                    'updated_at' => optional($l->updated_at)->toDateTimeString(),
                ];
            });

        return $this->unifiedResponse(true, 'Licenses retrieved successfully.', $licenses);
    }

    public function getLicenseById($id)
    {
        $l = \App\Models\License::with(['center:id,name'])->find($id);
        if (!$l) {
            return $this->unifiedResponse(false, 'License not found.', [], [], 404);
        }

        $fileUrl = $l->file_path
            ? asset('storage/' . ltrim($l->file_path, '/'))
            : null;

        $data = [
            'id'         => $l->id,
            'user_id'    => $l->user_id,
            'center_id'  => $l->center_id,
            'status'     => $l->status,
            'issued_by'  => $l->issued_by,
            'issue_date' => optional($l->issue_date)->toDateString() ?? (string)$l->issue_date,
            'file_path'  => $fileUrl,

            'center' => [
                'id'   => $l->center?->id,
                'name' => $l->center?->name,
            ],

            'user' => [
                'id'   => $l->user?->id,
                'name' => $l->user?->full_name,
            ],

            'created_at' => optional($l->created_at)->toDateTimeString(),
            'updated_at' => optional($l->updated_at)->toDateTimeString(),
        ];

        return $this->unifiedResponse(true, 'License retrieved successfully.', $data);
    }

    public function updateLicenseStatus($id, $status)
    {
        $l = \App\Models\License::find($id);
        if (!$l) {
            return $this->unifiedResponse(false, 'License not found.', [], [], 404);
        }

        $l->status = $status;
        $l->save();

        $l->loadMissing(['center:id,name']);

        $fileUrl = $l->file_path
            ? asset('storage/' . ltrim($l->file_path, '/'))
            : null;

        $data = [
            'id'         => $l->id,
            'user_id'    => $l->user_id,
            'center_id'  => $l->center_id,
            'status'     => $l->status,
            'issued_by'  => $l->issued_by,
            'issue_date' => optional($l->issue_date)->toDateString() ?? (string)$l->issue_date,
            'file_path'  => $fileUrl,

            'center' => [
                'id'   => $l->center?->id,
                'name' => $l->center?->name,
            ],

            'user' => [
                'id'   => $l->user?->id,
                'name' => $l->user?->full_name,
            ],

            'created_at' => optional($l->created_at)->toDateTimeString(),
            'updated_at' => optional($l->updated_at)->toDateTimeString(),
        ];

        return $this->unifiedResponse(true, 'License status updated.', $data);
    }


    // public function listLicenses()
    // {
    //     $licenses = License::with(['user', 'center'])->latest()->get();
    //     return $this->unifiedResponse(true, 'Licenses retrieved successfully.', $licenses);
    // }

    // public function getLicenseById($id)
    // {
    //     $license = License::with(['user', 'center'])->find($id);

    //     if (!$license) {
    //         return $this->unifiedResponse(false, 'License not found.', [], [], 404);
    //     }

    //     return $this->unifiedResponse(true, 'License retrieved successfully.', $license);
    // }

    // public function updateLicenseStatus($id, $status)
    // {
    //     $license = License::find($id);

    //     if (!$license) {
    //         return $this->unifiedResponse(false, 'License not found.', [], [], 404);
    //     }

    //     $license->status = $status;
    //     $license->save();

    //     return $this->unifiedResponse(true, 'License status updated.', $license);
    // }
}
