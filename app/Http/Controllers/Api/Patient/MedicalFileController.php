<?php

namespace App\Http\Controllers\Api\Patient;

use App\Http\Controllers\Controller;
use App\Services\Secretary\MedicalFileService;
use Illuminate\Http\Request;

class MedicalFileController extends Controller
{
    protected $files;

    public function __construct(MedicalFileService $files)
    {
        $this->files = $files;
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:5120',
            'type' => 'nullable|string|max:50',
        ]);

        $userId = auth()->id(); // 🔒 مهم: لا نأخذ user_id من الريكوست
        return $this->files->uploadMedicalFile($request, $userId);
    }

    public function index()
    {
        $userId = auth()->id();
        return $this->files->listPatientFiles($userId);
    }

    public function destroy($fileId)
    {
        $userId = auth()->id();
        return $this->files->deleteMedicalFile($userId, $fileId);
    }
}
