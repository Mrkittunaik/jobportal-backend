<?php

namespace Tests\Feature\Concerns;

use App\Models\CandidateProfile;
use App\Models\Company;
use App\Models\User;

trait MakesAccounts
{
    protected function candidate(): User
    {
        $u = User::factory()->create();
        CandidateProfile::create(['user_id' => $u->id]);
        return $u;
    }

    protected function company(string $status = 'approved'): User
    {
        $u = User::factory()->role('company')->create();
        Company::create(['user_id' => $u->id, 'company_name' => 'Co '.$u->id, 'verification_status' => $status]);
        return $u;
    }

    protected function admin(): User
    {
        return User::factory()->role('admin')->create();
    }

    protected function jobPayload(array $o = []): array
    {
        return array_merge([
            'title' => 'Laravel Developer', 'description' => 'Build APIs', 'location' => 'Hyderabad', 'city' => 'Hyderabad',
            'state' => 'Telangana', 'employment_type' => 'full-time', 'experience_min' => 1, 'salary_min' => 300000,
            'salary_max' => 600000, 'status' => 'published', 'skills' => ['Laravel', 'MySQL'],
        ], $o);
    }
}
