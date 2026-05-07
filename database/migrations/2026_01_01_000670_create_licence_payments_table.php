<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licence_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_annual_declaration_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('payment_reference', 120)->unique();
            $table->string('gateway_reference', 120)->nullable()->index();
            $table->string('provider_event_id', 191)->nullable();
            $table->string('gateway_name', 50)->index();
            $table->decimal('amount', 14, 2);
            $table->decimal('amount_allocated', 14, 2)->default(0);
            $table->decimal('balance_before', 14, 2)->nullable();
            $table->decimal('balance_after', 14, 2)->nullable();
            $table->string('currency', 3)->default('NGN');
            $table->string('payment_status', 50)->default('pending')->index();
            $table->timestampTz('paid_at')->nullable();
            $table->json('raw_response_json')->nullable();
            $table->timestampTz('processed_at')->nullable();
            $table->boolean('is_reconciled')->default(false)->index();
            $table->timestampTz('reconciled_at')->nullable();
            $table->foreignId('reconciled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->index(['licence_id', 'payment_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE licence_payments ADD CONSTRAINT licence_payments_status_check CHECK (payment_status IN ('pending','processing','paid','failed','cancelled','pending_offline'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('licence_payments');
    }
};
