<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('licensing_fee_plans', function (Blueprint $table) {
            $table->id();
            $table->string('institution_type', 80)->index();
            $table->string('basis_type', 50)->index();
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->decimal('flat_amount', 14, 2)->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('effective_from_year');
            $table->unsignedSmallInteger('effective_to_year')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->json('metadata_json')->nullable();
            $table->timestampsTz();
            $table->index(['institution_type', 'basis_type', 'effective_from_year']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE licensing_fee_plans ADD CONSTRAINT licensing_fee_plans_basis_type_check CHECK (basis_type IN ('per_student','per_member','per_branch','flat_rate'))");
        }

        // Legacy rename: `church` → `religious_organization` (was a separate data migration).
        DB::table('institutions')->where('institution_type', 'church')->update(['institution_type' => 'religious_organization']);
        DB::table('licensing_fee_plans')->where('institution_type', 'church')->delete();
    }

    public function down(): void
    {
        Schema::dropIfExists('licensing_fee_plans');
    }
};
