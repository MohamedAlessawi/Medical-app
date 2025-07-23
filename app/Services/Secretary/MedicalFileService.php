<?php

namespace App\Services\Secretary;

use App\Models\MedicalFile;
use App\Traits\FileUploadTrait;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MedicalFileService
{
    use FileUploadTrait;

    public function uploadMedicalFile(Request $request, $userId)
    {
        $path = $this->handleFileUpload($request, 'file', 'medical_files');
        if ($path) {
            $medicalFile = MedicalFile::create([
                'user_id' => $userId,
                'file_url' => $path,
                'type' => $request->input('type', 'report'),
                'upload_date' => Carbon::now()->toDateString(),
            ]);
            return $this->jsonResponse('Medical file uploaded successfully', 200, ['medical_file' => $medicalFile]);
        }
        return $this->jsonResponse('No file uploaded', 400);
    }
}
