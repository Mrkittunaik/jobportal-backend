<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Services\CandidateService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CandidateSkillController extends Controller
{
    public function __construct(private CandidateService $svc) {}

    public function index(Request $r)
    {
        return ApiResponse::success($this->svc->skills($r->user()));
    }

    /** Body: { "skills": ["Laravel", "MySQL"] } replaces the candidate's skill list. */
    public function store(Request $r)
    {
        $d = $r->validate(['skills' => ['required', 'array', 'max:50'], 'skills.*' => ['string', 'max:60']]);
        return ApiResponse::success($this->svc->syncSkills($r->user(), $d['skills']), 'Skills updated');
    }
}
