<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\ExperienceRequest;
use App\Services\CandidateService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class ExperienceController extends Controller
{
    private const REL = 'experiences';

    public function __construct(private CandidateService $svc) {}

    public function index(Request $r)
    {
        return ApiResponse::success($this->svc->listItems($r->user(), self::REL));
    }

    public function store(ExperienceRequest $r)
    {
        return ApiResponse::created($this->svc->createItem($r->user(), self::REL, $r->validated()), 'Experience added');
    }

    public function update(ExperienceRequest $r, int $id)
    {
        return ApiResponse::success($this->svc->updateItem($r->user(), self::REL, $id, $r->validated()), 'Experience updated');
    }

    public function destroy(Request $r, int $id)
    {
        $this->svc->deleteItem($r->user(), self::REL, $id);
        return ApiResponse::success(null, 'Experience deleted');
    }
}
