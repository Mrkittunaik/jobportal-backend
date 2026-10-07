<?php

namespace App\Http\Controllers\File;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileUploadRequest;
use App\Models\File;
use App\Services\FileUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FileController extends Controller
{
    /** Which categories each role may upload here. Resumes/certificates have their own endpoints. */
    private const ALLOWED = [
        'candidate' => ['profile_photo', 'other'],
        'company' => ['company_logo', 'job_image', 'video', 'other'],
        'admin' => ['profile_photo', 'company_logo', 'job_image', 'video', 'other'],
    ];

    public function __construct(private FileUploadService $uploads) {}

    public function upload(FileUploadRequest $r)
    {
        $user = $r->user();
        $category = $r->input('category');

        if (in_array($category, ['resume', 'certificate'], true)) {
            return ApiResponse::error('Use /api/candidate/resume or /api/candidate/certificates for this file type', 422);
        }
        if (! in_array($category, self::ALLOWED[$user->role], true)) {
            return ApiResponse::error("You cannot upload '$category' files", 403);
        }

        $old = $user->files()->where('category', $category)->get(); // one photo/logo at a time
        $file = $this->uploads->upload($r->file('file'), $user, $category);
        $ref = $file->url ?? $file->storage_path;

        if ($category === 'profile_photo') $user->candidateProfile()->updateOrCreate([], ['profile_photo' => $ref]);
        if ($category === 'company_logo' && $user->isCompany()) $user->company()->update(['logo' => $ref]);
        if (in_array($category, ['profile_photo', 'company_logo'], true)) $old->each(fn ($f) => $this->uploads->delete($f));

        return ApiResponse::created($file, 'File uploaded');
    }

    public function destroy(Request $r, int $id)
    {
        $file = File::findOrFail($id);
        Gate::forUser($r->user())->authorize('delete', $file);

        $ref = $file->url ?? $file->storage_path;
        $this->uploads->delete($file);
        \App\Models\CandidateProfile::where('user_id', $file->user_id)->where('profile_photo', $ref)->update(['profile_photo' => null]);
        \App\Models\Company::where('user_id', $file->user_id)->where('logo', $ref)->update(['logo' => null]);

        return ApiResponse::success(null, 'File deleted');
    }
}
