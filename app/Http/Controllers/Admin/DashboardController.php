<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Support\ApiResponse;

class DashboardController extends Controller
{
    public function index(AdminService $admin)
    {
        return ApiResponse::success($admin->dashboard());
    }
}
