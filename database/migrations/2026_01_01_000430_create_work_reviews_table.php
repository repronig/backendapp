<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('decision', 50)->index();
            $table->string('reason_code', 50)->nullable();
            $table->text('review_note')->nullable();
            $table->boolean('evidence_requested')->default(false);
            $table->timestampTz('reviewed_at');
            $table->json('metadata_json')->nullable();
            $table->timestampsTz();
            $table->index(['work_id', 'decision']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE work_reviews ADD CONSTRAINT work_reviews_decision_check CHECK (decision IN ('approved','rejected','changes_requested','restricted','verified','disputed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_reviews');
    }
};
