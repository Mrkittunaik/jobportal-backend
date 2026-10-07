<?php

namespace App\Policies;

use App\Models\Job;
use App\Models\User;

class JobPolicy
{
    /** A company manages only its own jobs; admin manages all. */
    public function manage(User $user, Job $job): bool
    {
        if ($user->isAdmin()) return true;
        return $user->isCompany() && $user->company?->id === $job->company_id;
    }
}
