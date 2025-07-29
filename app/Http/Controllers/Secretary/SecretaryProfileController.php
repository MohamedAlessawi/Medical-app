<?php

namespace App\Http\Controllers\Secretary;

use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\UpdateSecretaryProfileRequest;
use App\Services\Secretary\SecretaryProfileService;

class SecretaryProfileController extends Controller
{
    protected $profileService;

    public function __construct(SecretaryProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function show()
    {
        return $this->profileService->showProfile();
    }

    public function update(UpdateSecretaryProfileRequest $request)
    {
        return $this->profileService->updateProfile($request);
    }
}
