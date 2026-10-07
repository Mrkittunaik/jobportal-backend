<?php

namespace App\Services;

use App\Models\File;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Reusable upload/delete over the configured disk (Cloudflare R2 by default).
 * Switching provider = change UPLOAD_DISK + the disk config; callers don't change.
 * MySQL stores metadata only; bytes live in the bucket.
 */
class FileUploadService
{
    public function disk(): string
    {
        return config('uploads.disk', 'r2');
    }

    public function upload(UploadedFile $file, User $user, string $category = 'other'): File
    {
        $ext = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $name = Str::uuid().'.'.$ext;
        $path = "{$category}/{$user->id}/{$name}";

        Storage::disk($this->disk())->putFileAs("{$category}/{$user->id}", $file, $name);

        return File::create([
            'user_id' => $user->id,
            'file_name' => $name,
            'original_name' => Str::limit($file->getClientOriginalName(), 190, ''),
            'file_type' => $file->getMimeType() ?: 'application/octet-stream',
            'file_size' => $file->getSize(),
            'storage_path' => $path,
            'url' => $this->publicUrl($path),
            'category' => $category,
        ]);
    }

    public function delete(File $file): void
    {
        Storage::disk($this->disk())->delete($file->storage_path);
        $file->delete();
    }

    /** Short-lived link for private objects (resumes/certificates). */
    public function temporaryUrl(File $file, int $minutes = 10): string
    {
        return Storage::disk($this->disk())->temporaryUrl($file->storage_path, now()->addMinutes($minutes));
    }

    private function publicUrl(string $path): ?string
    {
        $base = config('filesystems.disks.'.$this->disk().'.url');
        return $base ? rtrim($base, '/').'/'.$path : null;
    }

    /** Validation rules for a single file in the given category (mime + size from config/uploads.php). */
    public static function fileRules(string $category): array
    {
        $cfg = config("uploads.categories.$category", config('uploads.categories.other'));
        return ['required', 'file', 'mimes:'.implode(',', $cfg['mimes']), 'max:'.$cfg['max_kb']];
    }
}
