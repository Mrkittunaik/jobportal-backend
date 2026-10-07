<?php

namespace App\Http\Controllers\Application;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApplicationRequest;
use App\Services\ApplicationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function __construct(private ApplicationService $apps) {}

    public function apply(ApplicationRequest $r, int $job)
    {
        return ApiResponse::created($this->apps->apply($r->user(), $job, $r->validated()), 'Application submitted');
    }

    /** Candidate: own applications. Company: applicants for own jobs. Admin: everything. */
    public function index(Request $r)
    {
        $u = $r->user();
        return ApiResponse::success($u->isCandidate()
            ? $this->apps->listForCandidate($u, $r->query())
            : $this->apps->listForCompany($u, $r->query()));
    }

    public function show(Request $r, int $id)
    {
        return ApiResponse::success($this->apps->view($r->user(), $id));
    }

    public function updateStatus(Request $r, int $id)
    {
        $d = $r->validate([
            'status' => ['required', 'in:under_review,shortlisted,interview,selected,rejected'],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        return ApiResponse::success($this->apps->updateStatus($r->user(), $id, $d['status'], $d['note'] ?? null), 'Application status updated');
    }

    public function withdraw(Request $r, int $id)
    {
        return ApiResponse::success($this->apps->withdraw($r->user(), $id), 'Application withdrawn');
    }
}
