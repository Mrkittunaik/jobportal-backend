<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $t->string('company_name')->index();
            $t->string('logo')->nullable();
            $t->text('description')->nullable();
            $t->string('website')->nullable();
            $t->string('email')->nullable();
            $t->string('phone', 20)->nullable();
            $t->string('industry', 100)->nullable();
            $t->string('location')->nullable();
            $t->string('city', 100)->nullable();
            $t->string('state', 100)->nullable();
            $t->string('company_size', 50)->nullable();
            $t->enum('verification_status', ['pending', 'approved', 'rejected', 'blocked'])->default('pending')->index();
            $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('companies'); }
};
