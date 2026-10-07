<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AdminService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function __construct(private AdminService $admin) {}

    public function index(Request $r)
    {
        $like = $r->filled('search') ? '%'.addcslashes($r->search, '%_\\').'%' : null;
        $users = User::query()
            ->when($r->filled('role'), fn ($q) => $q->where('role', $r->role))
            ->when($r->filled('status'), fn ($q) => $q->where('status', $r->status))
            ->when($like, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)))
            ->latest('id')->paginate(min((int) $r->query('per_page', 20), 100));
        return ApiResponse::success($users);
    }

    public function block(int $id)
    {
        return ApiResponse::success($this->admin->setUserStatus(User::findOrFail($id), 'blocked'), 'User blocked');
    }

    public function unblock(int $id)
    {
        return ApiResponse::success($this->admin->setUserStatus(User::findOrFail($id), 'active'), 'User unblocked');
    }
}
