<?php

namespace App\Policies;

use App\Models\Application;
use App\Models\User;

class ApplicationPolicy
{
    private function ownsJob(User $u, Application $a): bool
    {
        $a->loadMissing('job');
        return $u->isCompany() && $u->company?->id === $a->job->company_id;
    }

    public function view(User $u, Application $a): bool
    {
        return $u->isAdmin() || $a->candidate_id === $u->id || $this->ownsJob($u, $a);
    }

    public function updateStatus(User $u, Application $a): bool
    {
        return $u->isAdmin() || $this->ownsJob($u, $a);
    }

    public function withdraw(User $u, Application $a): bool
    {
        return $a->candidate_id === $u->id;
    }
}
