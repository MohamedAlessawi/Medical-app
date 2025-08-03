<?php

namespace App\Services\Secretary;

use App\Traits\ApiResponseTrait;
use Illuminate\Support\Facades\Auth;

class SecretaryProfileService
{
    use ApiResponseTrait;

    public function showProfile()
    {
        $user = Auth::user();
        $center = $user->secretaries->first()->center;

        return $this->unifiedResponse(true, 'Secretary profile fetched successfully.', [
            'user_id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'profile_photo' => $user->profile_photo,
            'role' => $user->roles->pluck('name')->first(),
            'center_name' => $center->name ?? null,
        ]);
    }

    public function updateProfile($request)
    {
        $user = Auth::user();

        $data = $request->only(['full_name', 'email', 'phone', 'address']);

        if ($request->hasFile('profile_photo')) {
            $image = $request->file('profile_photo');
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('uploads/profile_photos'), $imageName);
            $data['profile_photo'] = 'uploads/profile_photos/' . $imageName;
        }

        $user->update($data);

        return $this->unifiedResponse(true, 'Profile updated successfully.', [
            'user_id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'profile_photo' => $user->profile_photo,
            'role' => $user->roles->pluck('name')->first(),
        ]);
    }
}
