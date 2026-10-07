<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\Feature\Concerns\MakesAccounts;
use Tests\TestCase;

class JobFlowTest extends TestCase
{
    use RefreshDatabase, MakesAccounts;

    public function test_unapproved_company_can_only_save_drafts(): void
    {
        Sanctum::actingAs($this->company('pending'));
        $this->postJson('/api/company/jobs', $this->jobPayload())->assertForbidden();
        $this->postJson('/api/company/jobs', $this->jobPayload(['status' => 'draft']))->assertCreated();
        $this->getJson('/api/jobs')->assertJsonPath('data.total', 0); // drafts never public
    }

    public function test_search_filters_and_pagination(): void
    {
        Sanctum::actingAs($this->company());
        $this->postJson('/api/company/jobs', $this->jobPayload())->assertCreated();
        $this->postJson('/api/company/jobs', $this->jobPayload(['title' => 'Sales Executive', 'city' => 'Pune', 'location' => 'Pune', 'employment_type' => 'part-time', 'skills' => ['Excel']]))->assertCreated();

        $this->app['auth']->forgetGuards();
        $this->getJson('/api/jobs?search=Laravel')->assertJsonPath('data.total', 1);
        $this->getJson('/api/jobs?city=Pune')->assertJsonPath('data.total', 1);
        $this->getJson('/api/jobs?skill=Excel')->assertJsonPath('data.total', 1);
        $this->getJson('/api/jobs?type=full-time')->assertJsonPath('data.total', 1);
        $this->getJson('/api/jobs?per_page=1')->assertJsonPath('data.per_page', 1)->assertJsonCount(1, 'data.data');
    }

    public function test_apply_status_tracking_and_notifications(): void
    {
        $company = $this->company();
        $cand = $this->candidate();

        Sanctum::actingAs($company);
        $jobId = $this->postJson('/api/company/jobs', $this->jobPayload())->json('data.id');

        Sanctum::actingAs($cand);
        $appId = $this->postJson("/api/jobs/$jobId/apply", ['cover_letter' => 'Hi'])->assertCreated()->json('data.id');
        $this->postJson("/api/jobs/$jobId/apply")->assertStatus(422); // duplicate

        Sanctum::actingAs($company);
        $this->getJson('/api/company/applicants')->assertJsonPath('data.total', 1);
        $this->getJson("/api/company/applicants/$appId")->assertOk()->assertJsonPath('data.status', 'under_review');
        $this->putJson("/api/company/applications/$appId/status", ['status' => 'shortlisted'])->assertOk();
        $this->putJson("/api/company/applications/$appId/status", ['status' => 'applied'])->assertStatus(422);
        $this->putJson("/api/applications/$appId/status", ['status' => 'selected'])->assertOk();
        $this->putJson("/api/applications/$appId/status", ['status' => 'rejected'])->assertStatus(422); // terminal

        Sanctum::actingAs($cand);
        $this->getJson('/api/candidate/applications')->assertJsonPath('data.data.0.status', 'selected');
        $types = collect($this->getJson('/api/notifications')->json('data.notifications.data'))->pluck('type');
        $this->assertTrue($types->contains('application_submitted'));
        $this->assertTrue($types->contains('application_shortlisted'));
        $this->assertTrue($types->contains('application_selected'));
        $this->assertDatabaseCount('application_status_history', 4);

        $this->putJson('/api/notifications/read-all')->assertOk();
        $this->getJson('/api/notifications')->assertJsonPath('data.unread_count', 0);
    }

    public function test_company_cannot_touch_another_companys_data(): void
    {
        $a = $this->company(); $b = $this->company(); $cand = $this->candidate();
        Sanctum::actingAs($a);
        $jobId = $this->postJson('/api/company/jobs', $this->jobPayload())->json('data.id');
        Sanctum::actingAs($cand);
        $appId = $this->postJson("/api/jobs/$jobId/apply")->json('data.id');

        Sanctum::actingAs($b);
        $this->putJson("/api/jobs/$jobId", ['title' => 'Hacked'])->assertForbidden();
        $this->deleteJson("/api/company/jobs/$jobId")->assertForbidden();
        $this->putJson("/api/company/applications/$appId/status", ['status' => 'rejected'])->assertForbidden();
        $this->getJson("/api/company/applicants/$appId")->assertForbidden();
        $this->getJson('/api/company/applicants')->assertJsonPath('data.total', 0);
    }

    public function test_candidate_can_withdraw_and_company_cannot_create_candidate_actions(): void
    {
        $company = $this->company(); $cand = $this->candidate();
        Sanctum::actingAs($company);
        $jobId = $this->postJson('/api/company/jobs', $this->jobPayload())->json('data.id');
        $this->postJson("/api/jobs/$jobId/apply")->assertForbidden();

        Sanctum::actingAs($cand);
        $appId = $this->postJson("/api/jobs/$jobId/apply")->json('data.id');
        $this->putJson("/api/applications/$appId/withdraw")->assertOk()->assertJsonPath('data.status', 'withdrawn');
    }
}
