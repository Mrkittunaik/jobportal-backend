<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\File;
use App\Services\FileUploadService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminFileController extends Controller
{
    public function index(Request $r)
    {
        $rows = File::query()->with('user:id,name,email')
            ->when($r->filled('category'), fn ($q) => $q->where('category', $r->category))
            ->when($r->filled('user_id'), fn ($q) => $q->where('user_id', (int) $r->user_id))
            ->latest('id')->paginate(min((int) $r->query('per_page', 20), 100));
        return ApiResponse::success($rows);
    }

    public function destroy(int $id, FileUploadService $uploads)
    {
        $file = File::findOrFail($id);
        $ref = $file->url ?? $file->storage_path;
        $uploads->delete($file);
        \App\Models\CandidateProfile::where('profile_photo', $ref)->update(['profile_photo' => null]);
        \App\Models\Company::where('logo', $ref)->update(['logo' => null]);
        return ApiResponse::success(null, 'File deleted');
    }
}
