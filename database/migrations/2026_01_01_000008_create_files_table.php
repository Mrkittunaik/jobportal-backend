<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Metadata only. The actual bytes live in Cloudflare R2.
        Schema::create('files', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('file_name');
            $t->string('original_name');
            $t->string('file_type', 100);
            $t->unsignedBigInteger('file_size');
            $t->string('storage_path');
            $t->string('url', 500)->nullable();
            $t->enum('category', ['profile_photo', 'resume', 'certificate', 'company_logo', 'job_image', 'video', 'other'])->default('other');
            $t->timestamps();
            $t->index(['user_id', 'category']);
        });
    }
    public function down(): void { Schema::dropIfExists('files'); }
};
