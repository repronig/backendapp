<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_work_import_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->unsignedInteger('row_number');
            $table->foreignId('work_id')->nullable()->constrained('works')->nullOnDelete();
            $table->string('status', 50)->default('pending')->index();
            $table->json('row_payload_json')->nullable();
            $table->json('readiness_errors_json')->nullable();
            $table->json('submit_errors_json')->nullable();
            $table->timestampsTz();

            $table->unique(['import_batch_id', 'row_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_work_import_items');
    }
};
