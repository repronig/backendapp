<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('licences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_annual_declaration_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('licence_number', 120)->unique();
            $table->string('licence_id_snapshot', 100)->nullable()->index();
            $table->unsignedSmallInteger('licence_year')->index();
            $table->string('agreement_version', 60)->nullable();
            $table->string('licence_status', 50)->default('draft')->index();
            $table->string('payment_status', 50)->default('pending')->index();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->decimal('negotiated_rate', 14, 2)->nullable();
            $table->decimal('amount_due', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('outstanding_amount', 14, 2)->default(0);
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('issued_at')->nullable();
            $table->timestampsTz();
            $table->index(['institution_id', 'licence_year']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE licences ADD CONSTRAINT licences_status_check CHECK (licence_status IN ('draft','pending_payment','active','expired','suspended','cancelled'))");
            DB::statement("ALTER TABLE licences ADD CONSTRAINT licences_payment_status_check CHECK (payment_status IN ('pending','partially_paid','paid','waived','failed'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('licences');
    }
};
