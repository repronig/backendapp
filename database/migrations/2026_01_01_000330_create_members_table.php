<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('association_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_code', 60)->unique();
            $table->string('member_type', 50)->default('author')->index();
            $table->string('member_provided_id', 100)->nullable();
            $table->string('approval_status', 50)->default('pending')->index();
            $table->string('account_status', 50)->default('pending')->index();
            $table->string('status_reason_code', 50)->nullable();
            $table->text('status_reason')->nullable();
            $table->foreignId('status_changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('status_changed_at')->nullable();
            $table->timestampTz('joined_at')->nullable();
            $table->timestampTz('activated_at')->nullable();
            $table->timestampsTz();
            $table->index(['association_id', 'approval_status']);
            $table->index(['account_status', 'approval_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE members ADD CONSTRAINT members_approval_status_check CHECK (approval_status IN ('pending','under_review','approved','rejected','changes_requested'))");
            DB::statement("ALTER TABLE members ADD CONSTRAINT members_account_status_check CHECK (account_status IN ('pending','active','suspended','inactive'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
