<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\JobRequest;
use App\Models\Job;
use App\Services\JobService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/** A company's own jobs (all statuses, with applicant counts). */
class CompanyJobController extends Controller
{
    public function __construct(private JobService $jobs) {}

    public function index(Request $r)
    {
        return ApiResponse::success($this->jobs->listForCompany($r->user()->company()->firstOrFail(), $r->query()));
    }

    public function show(Request $r, int $id)
    {
        return ApiResponse::success($this->jobs->findForCompany($r->user()->company()->firstOrFail(), $id));
    }

    public function store(JobRequest $r)
    {
        return ApiResponse::created($this->jobs->create($r->user()->company()->firstOrFail(), $r->validated()), 'Job created successfully');
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
