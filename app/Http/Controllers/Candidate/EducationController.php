<?php

namespace App\Http\Controllers\Candidate;

use App\Http\Controllers\Controller;
use App\Http\Requests\EducationRequest;
use App\Services\CandidateService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class EducationController extends Controller
{
    private const REL = 'educations';

    public function __construct(private CandidateService $svc) {}

    public function index(Request $r)
    {
        return ApiResponse::success($this->svc->listItems($r->user(), self::REL));
    }

    public function store(EducationRequest $r)
    {
        return ApiResponse::created($this->svc->createItem($r->user(), self::REL, $r->validated()), 'Education added');
    }

    public function update(EducationRequest $r, int $id)
    {
        return ApiResponse::success($this->svc->updateItem($r->user(), self::REL, $id, $r->validated()), 'Education updated');
    }

    public function destroy(Request $r, int $id)
    {
        $this->svc->deleteItem($r->user(), self::REL, $id);
        return ApiResponse::success(null, 'Education deleted');
    }
}
