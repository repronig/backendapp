<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institutions', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->string('name', 200)->index();
            $table->string('institution_type', 80)->index();
            $table->string('registration_number', 120)->nullable()->unique();
            $table->string('licence_id', 100)->nullable()->unique();
            $table->unsignedSmallInteger('year_established')->nullable();
            $table->string('email')->nullable()->index();
            $table->string('phone', 30)->nullable();
            $table->string('contact_person_name', 180)->nullable();
            $table->string('contact_person_title', 150)->nullable();
            $table->unsignedInteger('faculties_count')->nullable();
            $table->unsignedInteger('member_count')->nullable();
            $table->unsignedInteger('branches_count')->nullable();
            $table->string('onboarding_status', 50)->default('draft')->index();
            $table->string('account_status', 50)->default('pending_review')->index();
            $table->string('logo_path')->nullable();
            $table->string('governance_status', 50)->nullable()->index();
            $table->string('governance_reason_code', 50)->nullable();
            $table->text('governance_reason')->nullable();
            $table->foreignId('governance_changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('governance_changed_at')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 120)->nullable();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country', 120)->default('Nigeria');
            $table->string('postal_code', 30)->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('approved_at')->nullable();
            $table->timestampTz('licence_id_generated_at')->nullable();
            $table->timestampTz('licensing_terms_accepted_at')->nullable();
            $table->date('licensing_terms_acknowledged_on')->nullable();
            $table->string('licensing_terms_version_accepted', 64)->nullable();
            $table->timestampsTz();
            $table->index(['institution_type', 'account_status']);
            $table->index(['onboarding_status', 'account_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE institutions ADD CONSTRAINT institutions_onboarding_status_check CHECK (onboarding_status IN ('draft','submitted','under_review','approved','rejected'))");
            DB::statement("ALTER TABLE institutions ADD CONSTRAINT institutions_account_status_check CHECK (account_status IN ('pending_review','active','suspended','blocked','inactive'))");
            DB::statement("ALTER TABLE institutions ADD CONSTRAINT institutions_governance_status_check CHECK (governance_status IS NULL OR governance_status IN ('normal','restricted','suspended','blocked'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institutions');
    }
};
