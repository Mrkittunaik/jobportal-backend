<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Creates (or updates) the single admin account from environment variables.
 * Admins can never self-register; this is the only way one is created.
 *
 *   ADMIN_EMAIL=you@example.com ADMIN_PASSWORD='a-strong-password' php artisan db:seed --class=AdminSeeder
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');
        if (! $email || ! $password) {
            $this->command?->error('Set ADMIN_EMAIL and ADMIN_PASSWORD in .env first.');
            return;
        }
        $user = User::firstOrNew(['email' => strtolower($email)]);
        $user->fill(['name' => env('ADMIN_NAME', 'Administrator'), 'password' => $password, 'status' => 'active']);
        $user->role = User::ROLE_ADMIN;
        $user->save();
        $this->command?->info('Admin ready: ' . $user->email);
    }
}
