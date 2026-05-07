<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('adjustment_type', 50)->index();
            $table->decimal('amount', 14, 2);
            $table->string('reason_code', 50)->nullable();
            $table->text('reason')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestampTz('applied_at')->nullable();
            $table->timestampsTz();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE invoice_adjustments ADD CONSTRAINT invoice_adjustments_type_check CHECK (adjustment_type IN ('credit_note','debit_note','discount','write_off','manual_correction','manual_adjustment'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_adjustments');
    }
};
