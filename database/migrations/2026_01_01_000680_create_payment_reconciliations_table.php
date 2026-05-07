<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('payment_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licence_payment_id')->constrained('licence_payments')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 50)->default('processed')->index();
            $table->string('reason_code', 50)->nullable();
            $table->text('note')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE payment_reconciliations ADD CONSTRAINT payment_reconciliations_status_check CHECK (status IN ('processed','matched','unmatched','failed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_reconciliations');
    }
};
