<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Guarded: does nothing if a users table already exists (e.g. you have this migration locally).
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('users')) return;
        Schema::create('users', function (Blueprint $t) {
            $t->id();
            $t->string('name', 120);
            $t->string('email', 190)->unique();
            $t->string('phone', 20)->nullable();
            $t->timestamp('email_verified_at')->nullable();
            $t->string('password');
            $t->enum('role', ['candidate', 'company', 'admin'])->default('candidate')->index();
            $t->enum('status', ['active', 'blocked'])->default('active')->index();
            $t->rememberToken();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('users'); }
};
