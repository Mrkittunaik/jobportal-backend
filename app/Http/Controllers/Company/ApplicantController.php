<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ApplicantController extends Controller
{
    public function __construct(private ApplicationService $apps) {}

    public function index(Request $r)
    {
        return ApiResponse::success($this->apps->listForCompany($r->user(), $r->query()));
    }

    public function show(Request $r, int $id)
    {
        return ApiResponse::success($this->apps->view($r->user(), $id));
    }

    public function invite(Request $r)
    {
        $d = $r->validate([
            'candidate_id' => ['required', 'integer'],
            'job_id' => ['nullable', 'integer'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);
        $this->apps->invite($r->user(), $d['candidate_id'], $d['job_id'] ?? null, $d['message'] ?? null);
        return ApiResponse::success(null, 'Invitation sent');
    }
}
