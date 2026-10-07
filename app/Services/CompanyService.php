<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;

class CompanyService
{
    public function getProfile(User $user): Company
    {
        return $user->company()->firstOrFail();
    }

    public function updateProfile(User $user, array $d): Company
    {
        $company = $this->getProfile($user);
        // verification_status is admin-only; it is not in CompanyProfileRequest rules, and we strip it anyway
        unset($d['verification_status'], $d['user_id']);
        $company->update($d);
        return $company->fresh();
    }
}
