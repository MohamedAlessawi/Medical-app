<?php

namespace App\Services\SuperAdmin;

use App\Models\License;
use App\Traits\ApiResponseTrait;

class LicenseService
{
    use ApiResponseTrait;

    public function listLicenses()
    {
        $licenses = License::with(['user', 'center'])->latest()->get();
        return $this->unifiedResponse(true, 'Licenses retrieved successfully.', $licenses);
    }

    public function getLicenseById($id)
    {
        $license = License::with(['user', 'center'])->find($id);

        if (!$license) {
            return $this->unifiedResponse(false, 'License not found.', [], [], 404);
        }

        return $this->unifiedResponse(true, 'License retrieved successfully.', $license);
    }

    public function updateLicenseStatus($id, $status)
    {
        $license = License::find($id);

        if (!$license) {
            return $this->unifiedResponse(false, 'License not found.', [], [], 404);
        }

        $license->status = $status;
        $license->save();

        return $this->unifiedResponse(true, 'License status updated.', $license);
    }
}
