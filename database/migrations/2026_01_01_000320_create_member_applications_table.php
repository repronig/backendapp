<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_applications', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->string('application_reference', 16)->unique();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('association_id')->nullable()->constrained()->nullOnDelete();
            $table->string('applicant_type', 50)->default('author')->index();
            $table->string('member_author_type', 30)->nullable();
            $table->string('member_author_category', 60)->nullable();
            $table->string('application_status', 50)->default('draft')->index();
            $table->string('submission_stage', 50)->default('profile_incomplete')->index();
            $table->string('nationality', 100)->nullable();
            $table->string('country_of_residence', 100)->nullable();
            $table->boolean('is_diaspora')->default(false);
            $table->string('bank_name', 150)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_owner_name', 180)->nullable();
            $table->string('next_of_kin_name', 180)->nullable();
            $table->string('next_of_kin_phone', 50)->nullable();
            $table->string('publisher_organisation_name', 180)->nullable();
            $table->string('publisher_tin', 80)->nullable();
            $table->text('publisher_location_address')->nullable();
            $table->text('publisher_postal_address')->nullable();
            $table->string('publisher_email')->nullable();
            $table->string('publisher_phone', 50)->nullable();
            $table->boolean('consent_accepted')->default(false);
            $table->date('consent_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('member_provided_id', 100)->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->timestampsTz();
            $table->index(['association_id', 'application_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE member_applications ADD CONSTRAINT member_applications_status_check CHECK (application_status IN ('draft','submitted','approved','rejected','changes_requested'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('member_applications');
    }
};
