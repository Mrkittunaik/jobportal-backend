<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function register(array $over = [])
    {
        return $this->postJson('/api/auth/register', array_merge([
            'name' => 'Asha', 'email' => 'asha@example.com', 'password' => 'Password1', 'password_confirmation' => 'Password1',
            'role' => 'candidate',
        ], $over));
    }

    public function test_candidate_can_register_and_gets_profile_and_token(): void
    {
        $this->register()->assertCreated()->assertJsonPath('success', true)->assertJsonStructure(['data' => ['token', 'user']]);
        $this->assertDatabaseHas('candidate_profiles', ['user_id' => User::first()->id]);
    }

    public function test_company_registers_as_pending(): void
    {
        $this->register(['email' => 'hr@acme.com', 'role' => 'company', 'company_name' => 'Acme'])->assertCreated();
        $this->assertDatabaseHas('companies', ['company_name' => 'Acme', 'verification_status' => 'pending']);
    }

    public function test_admin_role_cannot_self_register(): void
    {
        $this->register(['role' => 'admin'])->assertStatus(422)->assertJsonPath('success', false);
    }

    public function test_login_me_and_role_guard(): void
    {
        $this->register();
        $token = $this->postJson('/api/auth/login', ['email' => 'asha@example.com', 'password' => 'Password1'])
            ->assertOk()->json('data.token');

        $this->withToken($token)->getJson('/api/auth/me')->assertOk()->assertJsonPath('data.email', 'asha@example.com');
        $this->withToken($token)->getJson('/api/company/profile')->assertForbidden();
        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_candidate_cannot_edit_another_users_education(): void
    {
        $a = $this->register(['email' => 'a@example.com'])->json('data.token');
        $b = $this->register(['email' => 'b@example.com'])->json('data.token');
        $id = $this->withToken($a)->postJson('/api/candidate/education', ['institution' => 'JNTU', 'degree' => 'B.Tech'])->json('data.id');
        $this->withToken($b)->putJson("/api/candidate/education/$id", ['institution' => 'X', 'degree' => 'Y'])->assertNotFound();
    }

    public function test_blocked_user_cannot_login(): void
    {
        $this->register();
        User::first()->update(['status' => 'blocked']);
        $this->postJson('/api/auth/login', ['email' => 'asha@example.com', 'password' => 'Password1'])->assertStatus(422);
    }
}
