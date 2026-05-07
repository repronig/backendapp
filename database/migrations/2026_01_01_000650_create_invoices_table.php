<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number', 120)->unique();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_annual_declaration_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignId('licence_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('invoice_type', 50)->default('licence_fee')->index();
            $table->unsignedSmallInteger('billing_year')->index();
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->decimal('subtotal_amount', 14, 2)->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->decimal('amount_paid', 14, 2)->default(0);
            $table->decimal('outstanding_amount', 14, 2)->default(0);
            $table->string('invoice_status', 50)->default('issued')->index();
            $table->string('currency', 3)->default('NGN');
            $table->json('metadata_json')->nullable();
            $table->timestampTz('issued_at')->nullable();
            $table->timestampTz('paid_at')->nullable();
            $table->timestampTz('last_due_reminder_sent_at')->nullable();
            $table->timestampTz('last_overdue_reminder_sent_at')->nullable();
            $table->timestampsTz();
            $table->index(['institution_id', 'invoice_status']);
            $table->index(['billing_year', 'invoice_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE invoices ADD CONSTRAINT invoices_status_check CHECK (invoice_status IN ('issued','partially_paid','paid','overdue','cancelled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
