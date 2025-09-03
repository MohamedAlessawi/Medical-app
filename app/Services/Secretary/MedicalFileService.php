<?php

namespace App\Services\Secretary;

use App\Models\MedicalFile;
use App\Traits\FileUploadTrait;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MedicalFileService
{
    use FileUploadTrait, ApiResponseTrait;

    public function uploadMedicalFile(Request $request, $userId)
    {
        $upload = $this->handleFileUpload($request, 'file', 'medical_files');

        if (!$upload || empty($upload['path'])) {
            return $this->unifiedResponse(false, 'No file uploaded', [], [], 400);
        }

        $medicalFile = MedicalFile::create([
            'user_id'     => $userId,
            'file_url'    => $upload['path'],
            'type'        => $request->input('type', 'report'),
            'upload_date' => Carbon::now()->toDateString(),
        ]);

        return $this->unifiedResponse(true, 'Medical file uploaded successfully', [
            'medical_file' => [
                'id'            => $medicalFile->id,
                'user_id'       => $medicalFile->user_id,
                'type'          => $medicalFile->type,
                'upload_date'   => $medicalFile->upload_date,
                'file_url'      => Storage::disk('public')->url($medicalFile->file_url),
                // 'file_full_url' => Storage::disk('public')->url($medicalFile->file_url),
            ]
        ], [], 200);
    }

    public function listPatientFiles($userId)
    {
        $files = MedicalFile::where('user_id', $userId)
            ->orderByDesc('created_at')
            ->get();

        $data = $files->map(function ($f) {
            return [
                'id'            => $f->id,
                'user_id'       => $f->user_id,
                'type'          => $f->type,
                'upload_date'   => $f->upload_date,
                'file_url'      => $f->file_url ? Storage::disk('public')->url($f->file_url) : null,
                // 'file_full_url' => $f->file_url ? Storage::disk('public')->url($f->file_url) : null,
                'created_at'    => optional($f->created_at)->toDateTimeString(),
            ];
        });

        return $this->unifiedResponse(true, 'Medical files fetched successfully', $data);
    }

    public function deleteMedicalFile($userId, $fileId)
    {
        $file = MedicalFile::where('id', $fileId)
            ->where('user_id', $userId)
            ->first();

        if (!$file) {
            return $this->unifiedResponse(false, 'Medical file not found', [], [], 404);
        }

        if ($file->file_url && Storage::disk('public')->exists($file->file_url)) {
            Storage::disk('public')->delete($file->file_url);
        }

        $file->delete();

        return $this->unifiedResponse(true, 'Medical file deleted successfully', [
            'deleted_file_id' => $fileId,
            'user_id'         => $userId,
        ]);
    }

}
