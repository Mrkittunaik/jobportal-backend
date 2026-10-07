<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\CandidateProfileRequest;
use App\Services\CandidateService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CandidateProfileController extends Controller
{
    public function __construct(private CandidateService $svc) {}

    public function show(Request $r)
    {
        return ApiResponse::success($this->svc->getProfile($r->user()));
    }

    public function update(CandidateProfileRequest $r)
    {
        return ApiResponse::success($this->svc->updateProfile($r->user(), $r->validated()), 'Profile updated');
    }
}
