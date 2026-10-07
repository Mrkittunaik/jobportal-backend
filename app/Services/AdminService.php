<?php

namespace App\Services;

use App\Models\Application;
use App\Models\Company;
use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AdminService
{
    public function __construct(private NotificationService $notifications) {}

    public function dashboard(): array
    {
        return Cache::remember('admin:dashboard', 60, function () {
            $apps = Application::selectRaw('status, count(*) as c')->groupBy('status')->pluck('c', 'status');
            return [
                'total_candidates' => User::where('role', 'candidate')->count(),
                'total_companies' => Company::count(),
                'total_jobs' => Job::count(),
                'active_jobs' => Job::published()->count(),
                'total_applications' => (int) $apps->sum(),
                'pending_companies' => Company::where('verification_status', 'pending')->count(),
                'selected_candidates' => (int) ($apps['selected'] ?? 0),
                'rejected_applications' => (int) ($apps['rejected'] ?? 0),
            ];
        });
    }

    public function setCompanyStatus(Company $company, string $status): Company
    {
        DB::transaction(function () use ($company, $status) {
            $company->update(['verification_status' => $status]);
            if ($status === 'blocked') $company->jobs()->where('status', 'published')->update(['status' => 'closed']);
        });
        Cache::forget('admin:dashboard');
        JobService::bumpCache();

        $msg = [
            'approved' => ['Company approved', 'Your company has been approved. You can now publish jobs.'],
            'rejected' => ['Company rejected', 'Your company registration was rejected. Please update your profile or contact support.'],
            'blocked' => ['Company blocked', 'Your company has been blocked and its jobs were closed.'],
        ][$status] ?? null;
        if ($msg) $this->notifications->notify($company->user_id, 'company_'.$status, $msg[0], $msg[1]);
        return $company->fresh();
    }

    public function setUserStatus(User $user, string $status): User
    {
        abort_if($user->isAdmin(), 403, 'Admin accounts cannot be blocked here');
        $user->update(['status' => $status]);
        if ($status === 'blocked') $user->tokens()->delete(); // kicks them out immediately
        return $user->fresh();
    }
}
