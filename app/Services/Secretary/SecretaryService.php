<?php

namespace App\Services\Secretary;

use App\Models\Secretary;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;

class SecretaryService
{
    use ApiResponseTrait, FileUploadTrait;


    public function getProfile($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->unifiedResponse(false, 'Secretary not found.', [], [], 404);
        }
        
        $secretary = Secretary::where('user_id', $userId)->first();
        $data = [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'profile_photo' => $user->profile_photo,
            'address' => $user->address,
            'role' => 'secretary',
            'center_id' => $secretary ? $secretary->center_id : null,
        ];
        return $this->unifiedResponse(true, 'Secretary profile fetched successfully.', $data);
    }


    public function updateProfile($userId, $data)
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->unifiedResponse(false, 'Secretary not found.', [], [], 404);
        }
        $user->update([
            'full_name' => $data['full_name'] ?? $user->full_name,
            'email' => $data['email'] ?? $user->email,
            'phone' => $data['phone'] ?? $user->phone,
            'address' => $data['address'] ?? $user->address,
        ]);
        return $this->unifiedResponse(true, 'Secretary profile updated successfully.', $user);
    }


    public function updateProfilePhoto(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->unifiedResponse(false, 'Secretary not found.', [], [], 404);
        }
        $path = $this->handleFileUpload($request, 'profile_photo', 'profile_photos');
        if ($path) {
            $user->profile_photo = $path;
            $user->save();
            return $this->unifiedResponse(true, 'Profile photo updated successfully.', ['profile_photo' => $path]);
        }
        return $this->unifiedResponse(false, 'No photo uploaded.', [], [], 400);
    }
}
