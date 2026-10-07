<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\FileUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidateFileController extends Controller
{
    public function __construct(private FileUploadService $uploads) {}

    /** POST multipart: file (pdf/doc/docx), title?, is_primary? */
    public function resume(Request $r)
    {
        $r->validate([
            'file' => FileUploadService::fileRules('resume'),
            'title' => ['nullable', 'string', 'max:190'],
            'is_primary' => ['nullable', 'boolean'],
        ]);
        $user = $r->user();
        abort_if($user->resumes()->count() >= 5, 422, 'You can store up to 5 resumes. Delete one first.');

        $resume = DB::transaction(function () use ($r, $user) {
            $file = $this->uploads->upload($r->file('file'), $user, 'resume');
            $primary = $r->boolean('is_primary') || ! $user->resumes()->exists();
            if ($primary) $user->resumes()->update(['is_primary' => false]);
            return $user->resumes()->create([
                'file_id' => $file->id, 'title' => $r->input('title', $file->original_name), 'is_primary' => $primary,
            ]);
        });
        return ApiResponse::created($resume->load('file'), 'Resume uploaded');
    }

    /** POST multipart: file (pdf/jpg/png), name, issuer?, issued_date? */
    public function certificate(Request $r)
    {
        $r->validate([
            'file' => FileUploadService::fileRules('certificate'),
            'name' => ['required', 'string', 'max:190'],
            'issuer' => ['nullable', 'string', 'max:190'],
            'issued_date' => ['nullable', 'date'],
        ]);
        $user = $r->user();
        $cert = DB::transaction(function () use ($r, $user) {
            $file = $this->uploads->upload($r->file('file'), $user, 'certificate');
            return $user->certificates()->create([
                'file_id' => $file->id, 'name' => $r->name, 'issuer' => $r->issuer, 'issued_date' => $r->issued_date,
            ]);
        });
        return ApiResponse::created($cert->load('file'), 'Certificate uploaded');
    }
}
