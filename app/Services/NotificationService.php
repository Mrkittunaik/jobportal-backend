<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * Database notifications now. deliver() is the single seam where email / push /
 * real-time broadcasting can be added later without touching callers.
 */
class NotificationService
{
    public function notify(User|int $user, string $type, string $title, ?string $message = null, array $data = []): Notification
    {
        $n = Notification::create([
            'user_id' => $user instanceof User ? $user->id : $user,
            'type' => $type, 'title' => $title, 'message' => $message, 'data' => $data ?: null,
        ]);
        $this->deliver($n);
        return $n;
    }

    /** Send the same notification to many users (admin announcements). Returns recipient count. */
    public function broadcast(?string $role, string $title, ?string $message): int
    {
        $count = 0;
        User::where('status', 'active')->when($role && $role !== 'all', fn ($q) => $q->where('role', $role))
            ->select('id')->chunkById(500, function ($users) use ($title, $message, &$count) {
                $now = now();
                $rows = $users->map(fn ($u) => [
                    'user_id' => $u->id, 'type' => 'system', 'title' => $title, 'message' => $message,
                    'data' => null, 'created_at' => $now, 'updated_at' => $now,
                ])->all();
                Notification::insert($rows);
                $count += count($rows);
            });
        return $count;
    }

    public function list(User $user, bool $unreadOnly = false, int $perPage = 20)
    {
        return $user->notifications()->when($unreadOnly, fn ($q) => $q->whereNull('read_at'))
            ->latest('id')->paginate(min(max($perPage, 1), 50));
    }

    public function unreadCount(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->count();
    }

    public function markRead(User $user, int $id): Notification
    {
        $n = $user->notifications()->findOrFail($id);
        if (! $n->read_at) $n->update(['read_at' => now()]);
        return $n;
    }

    public function markAllRead(User $user): int
    {
        return $user->notifications()->whereNull('read_at')->update(['read_at' => now()]);
    }

    protected function deliver(Notification $n): void
    {
        // Future: Mail::to(...), push, Pusher/Reverb broadcast. Intentionally empty.
    }
}
