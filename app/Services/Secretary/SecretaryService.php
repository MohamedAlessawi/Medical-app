<?php

namespace App\Services\Secretary;

use App\Models\Secretary;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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


        $updateData = [];
        if (isset($data['full_name'])) {
            $updateData['full_name'] = $data['full_name'];
        }
        if (isset($data['email'])) {
            $updateData['email'] = $data['email'];
        }
        if (isset($data['phone'])) {
            $updateData['phone'] = $data['phone'];
        }
        if (isset($data['address'])) {
            $updateData['address'] = $data['address'];
        }

        $user->update($updateData);


        $updatedUser = User::find($userId);
        return $this->unifiedResponse(true, 'Secretary profile updated successfully.', $updatedUser);
    }


    public function updateProfilePhoto(Request $request, $userId)
    {
        $user = User::find($userId);
        if (!$user) {
            return $this->unifiedResponse(false, 'Secretary not found.', [], [], 404);
        }

        if (!$request->hasFile('photo')) {
            return $this->unifiedResponse(false, 'No photo uploaded.', [], [], 400);
        }
        
        $file = $request->file('photo');
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        if (!in_array($file->getMimeType(), $allowedTypes)) {
            return $this->unifiedResponse(false, 'Invalid file type. Only images are allowed.', [], [], 400);
        }

        $path = $this->handleFileUpload($request, 'photo', 'profile_photos');
        if ($path) {

            if ($user->profile_photo && Storage::disk('public')->exists($user->profile_photo)) {
                Storage::disk('public')->delete($user->profile_photo);
            }

            $user->profile_photo = $path;
            $user->save();
            return $this->unifiedResponse(true, 'Profile photo updated successfully.', ['profile_photo' => $path]);
        }
        return $this->unifiedResponse(false, 'Failed to upload photo.', [], [], 400);
    }
}
