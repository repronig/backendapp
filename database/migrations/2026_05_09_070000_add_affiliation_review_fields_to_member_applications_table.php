<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_applications', function (Blueprint $table) {
            $table->string('affiliation_status', 30)->default('pending')->index()->after('application_status');
            $table->text('affiliation_review_note')->nullable()->after('notes');
            $table->foreignId('affiliation_reviewed_by_user_id')->nullable()->after('reviewed_by_user_id')->constrained('users')->nullOnDelete();
            $table->timestampTz('affiliation_reviewed_at')->nullable()->after('reviewed_at');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE member_applications ADD CONSTRAINT member_applications_affiliation_status_check CHECK (affiliation_status IN ('pending','validated','rejected'))");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE member_applications DROP CONSTRAINT IF EXISTS member_applications_affiliation_status_check');
        }

        Schema::table('member_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('affiliation_reviewed_by_user_id');
            $table->dropColumn([
                'affiliation_status',
                'affiliation_review_note',
                'affiliation_reviewed_at',
            ]);
        });
    }
};
