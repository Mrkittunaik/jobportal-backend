<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\MakesAccounts;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase, MakesAccounts;

    public function test_only_admin_can_see_dashboard(): void
    {
        Sanctum::actingAs($this->candidate());
        $this->getJson('/api/admin/dashboard')->assertForbidden();

        Sanctum::actingAs($this->admin());
        $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonStructure(['data' => [
            'total_candidates', 'total_companies', 'total_jobs', 'active_jobs', 'total_applications',
            'pending_companies', 'selected_candidates', 'rejected_applications',
        ]]);
    }

    public function test_admin_approves_company_which_can_then_publish(): void
    {
        $co = $this->company('pending');
        Sanctum::actingAs($this->admin());
        $this->putJson('/api/admin/companies/'.$co->company->id.'/approve')->assertOk();
        $this->assertSame('approved', Company::first()->verification_status);

        Sanctum::actingAs($co->fresh());
        $this->postJson('/api/company/jobs', $this->jobPayload())->assertCreated();
        $this->assertTrue($co->notifications()->where('type', 'company_approved')->exists());
    }

    public function test_blocking_user_and_company_unpublishes_jobs(): void
    {
        $co = $this->company();
        Sanctum::actingAs($co);
        $this->postJson('/api/company/jobs', $this->jobPayload())->assertCreated();

        Sanctum::actingAs($this->admin());
        $this->putJson('/api/admin/companies/'.$co->company->id.'/block')->assertOk();
        $this->assertDatabaseHas('jobs', ['status' => 'closed']);

        $cand = $this->candidate();
        $this->putJson("/api/admin/users/{$cand->id}/block")->assertOk();
        $this->assertSame('blocked', User::find($cand->id)->status);
    }

    public function test_admin_manages_skills_categories_and_announcements(): void
    {
        $cand = $this->candidate();
        Sanctum::actingAs($this->admin());
        $this->postJson('/api/admin/skills', ['name' => 'Rust'])->assertCreated();
        $this->postJson('/api/admin/skills', ['name' => 'Rust'])->assertStatus(422);
        $this->postJson('/api/admin/categories', ['name' => 'Legal Services'])->assertCreated()->assertJsonPath('data.slug', 'legal-services');
        $this->postJson('/api/admin/notifications', ['title' => 'Hello', 'role' => 'candidate'])->assertCreated()->assertJsonPath('data.recipients', 1);
        $this->assertTrue($cand->notifications()->where('type', 'system')->exists());
    }
}
