<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyProfileRequest;
use App\Services\CompanyService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class CompanyProfileController extends Controller
{
    public function __construct(private CompanyService $svc) {}

    public function show(Request $r)
    {
        return ApiResponse::success($this->svc->getProfile($r->user()));
    }

    public function update(CompanyProfileRequest $r)
    {
        return ApiResponse::success($this->svc->updateProfile($r->user(), $r->validated()), 'Company profile updated');
    }
}
