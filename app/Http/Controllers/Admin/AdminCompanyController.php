<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Services\AdminService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminCompanyController extends Controller
{
    public function __construct(private AdminService $admin) {}

    public function index(Request $r)
    {
        $like = $r->filled('search') ? '%'.addcslashes($r->search, '%_\\').'%' : null;
        $rows = Company::query()->with('user:id,name,email,status')->withCount('jobs')
            ->when($r->filled('status'), fn ($q) => $q->where('verification_status', $r->status))
            ->when($like, fn ($q) => $q->where('company_name', 'like', $like))
            ->latest('id')->paginate(min((int) $r->query('per_page', 20), 100));
        return ApiResponse::success($rows);
    }

    public function approve(int $id)
    {
        return ApiResponse::success($this->admin->setCompanyStatus(Company::findOrFail($id), 'approved'), 'Company approved');
    }

    public function reject(int $id)
    {
        return ApiResponse::success($this->admin->setCompanyStatus(Company::findOrFail($id), 'rejected'), 'Company rejected');
    }

    public function block(int $id)
    {
        return ApiResponse::success($this->admin->setCompanyStatus(Company::findOrFail($id), 'blocked'), 'Company blocked');
    }
}
