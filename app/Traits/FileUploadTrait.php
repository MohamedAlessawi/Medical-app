<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

trait FileUploadTrait
{
    public function handleFileUpload(Request $request, string $fileAttribute, string $directory, string $disk = 'public'): ?array
    {
        if (! $request->hasFile($fileAttribute)) {
            return null;
        }

        /** @var UploadedFile $file */
        $file = $request->file($fileAttribute);
        if (! $file->isValid()) {
            return null; // ممكن ترجع استثناء لو حابب
        }

        // اسم نظيف + احتفاظ بالامتداد
        $ext  = $file->getClientOriginalExtension();
        $name = Str::uuid()->toString().'.'.$ext;

        // التخزين
        $path = $file->storeAs($directory, $name, $disk);

        // توليد رابط عام
        $url = Storage::disk($disk)->url($path);

        return [
            'path' => $path,            // مثال: profile_photos/uuid.jpg
            'url'  => $url,             // مثال: https://your-domain/storage/profile_photos/uuid.jpg
            'name' => $name,            // uuid.jpg
            'size' => $file->getSize(), // بالبايت
            'mime' => $file->getMimeType(),
        ];
    }


    // public function handleFileUpload(Request $request, $fileAttribute, $directory)
    // {
    //     if($request->hasFile($fileAttribute)){
    //         return $request->file($fileAttribute)->store($directory, 'public');
    //     }
    //     return null ;
    // }

    // public static function saveFileAndGivePath($file)
    // {
    //     if ($file != null) {
    //         $path = $file->store('uploads');
    //         return $path;
    //     } else {
    //         return null;
    //     }
    // }
}
