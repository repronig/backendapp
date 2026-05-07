<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institution_annual_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->string('licence_id_snapshot', 100)->nullable();
            $table->unsignedSmallInteger('licensing_year')->index();
            $table->string('basis_type', 50)->index();
            $table->unsignedInteger('declared_units')->default(0);
            $table->unsignedInteger('declared_students_count')->nullable();
            $table->unsignedInteger('declared_members_count')->nullable();
            $table->unsignedInteger('declared_branches_count')->nullable();
            $table->unsignedInteger('declared_faculties_count')->nullable();
            $table->decimal('pricing_unit_cost', 14, 2)->nullable();
            $table->decimal('pricing_flat_amount', 14, 2)->nullable();
            $table->decimal('expected_amount', 14, 2)->default(0);
            $table->decimal('paid_amount', 14, 2)->default(0);
            $table->decimal('outstanding_amount', 14, 2)->default(0);
            $table->string('declaration_status', 50)->default('draft')->index();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('invoice_due_date')->nullable();
            $table->string('supporting_document_path')->nullable();
            $table->string('supporting_document_disk')->default('public');
            $table->string('supporting_document_name')->nullable();
            $table->string('supporting_document_mime_type')->nullable();
            $table->unsignedBigInteger('supporting_document_size')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestampsTz();
            $table->unique(['institution_id', 'licensing_year']);
            $table->index(['institution_id', 'declaration_status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE institution_annual_declarations ADD CONSTRAINT institution_annual_declarations_basis_type_check CHECK (basis_type IN ('per_student','per_member','per_branch','flat_rate'))");
            DB::statement("ALTER TABLE institution_annual_declarations ADD CONSTRAINT institution_annual_declarations_status_check CHECK (declaration_status IN ('draft','submitted','under_review','approved','rejected'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_annual_declarations');
    }
};
