<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->morphs('documentable');
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('category', 80)->index();
            $table->string('title', 180)->nullable();
            $table->string('document_type', 80)->nullable()->index();
            $table->string('visibility', 50)->default('private')->index();
            $table->text('description')->nullable();
            $table->string('storage_disk', 80)->default('public');
            $table->string('file_path')->nullable();
            $table->string('checksum', 128)->nullable()->index();
            $table->timestampTz('last_accessed_at')->nullable();
            $table->timestampTz('archived_at')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestampsTz();
            $table->index(['documentable_type', 'documentable_id', 'category']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE documents ADD CONSTRAINT documents_visibility_check CHECK (visibility IN ('private','restricted','internal','public'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
