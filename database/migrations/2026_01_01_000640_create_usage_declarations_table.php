<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usage_declarations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('reporting_year')->index();
            $table->string('declaration_status', 50)->default('draft')->index();
            $table->foreignId('submitted_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('declared_student_population')->nullable();
            $table->unsignedInteger('declared_academic_staff_count')->nullable();
            $table->unsignedInteger('declared_administrative_staff_count')->nullable();
            $table->unsignedInteger('declared_campuses_count')->nullable();
            $table->unsignedInteger('declared_library_capacity')->nullable();
            $table->text('declaration_notes')->nullable();
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();
            $table->unique(['licence_id', 'reporting_year']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE usage_declarations ADD CONSTRAINT usage_declarations_status_check CHECK (declaration_status IN ('draft','submitted','reviewed','rejected'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_declarations');
    }
};
