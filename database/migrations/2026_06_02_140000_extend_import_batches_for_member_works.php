<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->foreignId('member_id')->nullable()->after('created_by_user_id')->constrained('members')->nullOnDelete();
            $table->boolean('agreement_accepted')->nullable()->after('summary_json');
            $table->date('date_of_consent')->nullable()->after('agreement_accepted');
            $table->unsignedInteger('ready_rows')->default(0)->after('processed_rows');
            $table->unsignedInteger('submitted_rows')->default(0)->after('ready_rows');
        });
    }

    public function down(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('member_id');
            $table->dropColumn(['agreement_accepted', 'date_of_consent', 'ready_rows', 'submitted_rows']);
        });
    }
};
