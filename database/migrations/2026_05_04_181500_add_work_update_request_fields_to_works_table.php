<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->string('update_request_status', 30)->nullable()->after('is_restricted')->index();
            $table->timestamp('update_requested_at')->nullable()->after('update_request_status');
            $table->foreignId('update_requested_by_member_id')->nullable()->after('update_requested_at')->constrained('members')->nullOnDelete();
            $table->text('update_request_note')->nullable()->after('update_requested_by_member_id');
            $table->timestamp('update_request_reviewed_at')->nullable()->after('update_request_note');
            $table->foreignId('update_request_reviewed_by_user_id')->nullable()->after('update_request_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('update_request_review_note')->nullable()->after('update_request_reviewed_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('works', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('update_request_reviewed_by_user_id');
            $table->dropColumn('update_request_review_note');
            $table->dropColumn('update_request_reviewed_at');
            $table->dropColumn('update_request_note');
            $table->dropConstrainedForeignId('update_requested_by_member_id');
            $table->dropColumn('update_requested_at');
            $table->dropColumn('update_request_status');
        });
    }
};
