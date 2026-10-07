<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\ApplicationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CandidateApplicationController extends Controller
{
    public function __construct(private ApplicationService $apps) {}

    public function index(Request $r)
    {
        return ApiResponse::success($this->apps->listForCandidate($r->user(), $r->query()));
    }
}
