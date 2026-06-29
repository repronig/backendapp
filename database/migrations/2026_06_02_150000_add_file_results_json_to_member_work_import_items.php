<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('member_work_import_items', function (Blueprint $table) {
            $table->json('file_results_json')->nullable()->after('readiness_errors_json');
        });
    }

    public function down(): void
    {
        Schema::table('member_work_import_items', function (Blueprint $table) {
            $table->dropColumn('file_results_json');
        });
    }
};
