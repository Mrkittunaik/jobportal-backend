<?php

use App\Models\Job;
use App\Services\JobService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('jobs:expire', function () {
    $n = Job::where('status', 'published')->whereNotNull('application_deadline')
        ->where('application_deadline', '<', now()->toDateString())->update(['status' => 'expired']);
    JobService::bumpCache();
    $this->info("Expired {$n} job(s)");
})->purpose('Mark published jobs past their deadline as expired');

Schedule::command('jobs:expire')->dailyAt('00:10');
Schedule::command('sanctum:prune-expired --hours=24')->daily();
