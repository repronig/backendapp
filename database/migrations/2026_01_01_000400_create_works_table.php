<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('works', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number', 60)->unique();
            $table->string('type_of_work', 80)->index();
            $table->string('title', 255);
            $table->string('subtitle', 255)->nullable();
            $table->unsignedInteger('publication_year')->nullable();
            $table->text('synopsis')->nullable();
            $table->string('primary_language', 80)->nullable();
            $table->string('identifier_type', 30)->nullable()->index();
            $table->string('identifier_value', 120)->nullable()->index();
            $table->string('duplicate_fingerprint', 64)->nullable()->index();
            $table->string('doi', 190)->nullable()->index();
            $table->string('publisher_name', 180)->nullable();
            $table->string('target_market', 80)->nullable();
            $table->date('date_of_consent')->nullable();
            $table->string('production_status', 10)->nullable();
            $table->string('work_format', 80)->nullable();
            $table->boolean('agreement_accepted')->default(false);
            $table->string('other_work_type', 180)->nullable();
            $table->string('target_market_other', 180)->nullable();
            $table->text('notes')->nullable();
            $table->string('work_status', 50)->default('draft')->index();
            $table->string('verification_status', 50)->default('pending')->index();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('last_reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('last_reviewed_at')->nullable();
            $table->text('review_reason')->nullable();
            $table->boolean('is_disputed')->default(false)->index();
            $table->boolean('is_restricted')->default(false)->index();
            $table->string('governance_reason_code', 50)->nullable();
            $table->text('governance_reason')->nullable();
            $table->timestampsTz();
            $table->index(['member_id', 'work_status']);
            $table->index(['verification_status', 'is_disputed', 'is_restricted']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE works ADD CONSTRAINT works_work_status_check CHECK (work_status IN ('draft','submitted','under_review','verified','disputed','approved','restricted'))");
            DB::statement("ALTER TABLE works ADD CONSTRAINT works_verification_status_check CHECK (verification_status IN ('pending','under_review','verified','rejected','disputed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('works');
    }
};
