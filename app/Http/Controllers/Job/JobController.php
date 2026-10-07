<?php

namespace App\Http\Controllers\Job;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequest;
use App\Models\Company;
use App\Models\Job;
use App\Services\JobService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** Public browsing (index/show) + create/update/delete for a company owner or admin. */
class JobController extends Controller
{
    public function __construct(private JobService $jobs) {}

    public function index(Request $r)
    {
        return ApiResponse::success($this->jobs->search($r->query()));
    }

    public function show(int $id)
    {
        return ApiResponse::success($this->jobs->findPublic($id));
    }

    public function store(JobRequest $r)
    {
        $user = $r->user();
        $company = $user->isAdmin()
            ? Company::findOrFail($r->validate(['company_id' => ['required', 'integer']])['company_id'])
            : $user->company()->firstOrFail();
        return ApiResponse::created($this->jobs->create($company, $r->validated()), 'Job created successfully');
    }

    public function update(JobRequest $r, int $id)
    {
        $job = Job::with('company')->findOrFail($id);
        Gate::forUser($r->user())->authorize('manage', $job);
        return ApiResponse::success($this->jobs->update($job, $r->validated()), 'Job updated successfully');
    }

    public function destroy(Request $r, int $id)
    {
        $job = Job::findOrFail($id);
        Gate::forUser($r->user())->authorize('manage', $job);
        $this->jobs->delete($job);
        return ApiResponse::success(null, 'Job deleted successfully');
    }
}
