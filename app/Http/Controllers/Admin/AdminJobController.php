<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Job;
use App\Services\JobService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminJobController extends Controller
{
    public function __construct(private JobService $jobs) {}

    public function index(Request $r)
    {
        $like = $r->filled('search') ? '%'.addcslashes($r->search, '%_\\').'%' : null;
        $rows = Job::query()->with('company:id,company_name')->withCount('applications')
            ->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))
            ->when($r->filled('company_id'), fn ($q) => $q->where('company_id', (int) $r->company_id))
            ->when($like, fn ($q) => $q->where('title', 'like', $like))
            ->latest('id')->paginate(min((int) $r->query('per_page', 20), 100));
        return ApiResponse::success($rows);
    }

    public function publish(int $id)
    {
        $job = Job::with('company')->findOrFail($id);
        return ApiResponse::success($this->jobs->setStatus($job, 'published'), 'Job published');
    }

    public function unpublish(int $id)
    {
        $job = Job::with('company')->findOrFail($id);
        return ApiResponse::success($this->jobs->setStatus($job, 'draft'), 'Job unpublished');
    }

    public function destroy(int $id)
    {
        $this->jobs->delete(Job::findOrFail($id));
        return ApiResponse::success(null, 'Job deleted');
    }
}
