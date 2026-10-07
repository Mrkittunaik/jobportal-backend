<?php

namespace App\Http\Controllers\Notification;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private NotificationService $svc) {}

    public function index(Request $r)
    {
        return ApiResponse::success([
            'unread_count' => $this->svc->unreadCount($r->user()),
            'notifications' => $this->svc->list($r->user(), $r->boolean('unread'), (int) $r->query('per_page', 20)),
        ]);
    }

    public function read(Request $r, int $id)
    {
        return ApiResponse::success($this->svc->markRead($r->user(), $id), 'Marked as read');
    }

    public function readAll(Request $r)
    {
        return ApiResponse::success(['updated' => $this->svc->markAllRead($r->user())], 'All notifications marked as read');
    }
}
