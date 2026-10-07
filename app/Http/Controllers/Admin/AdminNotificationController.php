<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function index(Request $r)
    {
        return ApiResponse::success(Notification::with('user:id,name,email')->latest('id')->paginate(min((int) $r->query('per_page', 20), 100)));
    }

    /** Send an announcement to one user, a role, or everyone. */
    public function store(Request $r, NotificationService $svc)
    {
        $d = $r->validate([
            'title' => ['required', 'string', 'max:190'],
            'message' => ['nullable', 'string', 'max:2000'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'role' => ['nullable', 'in:candidate,company,all'],
        ]);
        if (! empty($d['user_id'])) {
            $svc->notify($d['user_id'], 'system', $d['title'], $d['message'] ?? null);
            $count = 1;
        } else {
            $count = $svc->broadcast($d['role'] ?? 'all', $d['title'], $d['message'] ?? null);
        }
        return ApiResponse::created(['recipients' => $count], 'Notification sent');
    }

    public function destroy(int $id)
    {
        Notification::findOrFail($id)->delete();
        return ApiResponse::success(null, 'Notification deleted');
    }
}
