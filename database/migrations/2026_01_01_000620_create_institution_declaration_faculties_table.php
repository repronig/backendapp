<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institution_declaration_faculties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_annual_declaration_id')->constrained()->cascadeOnDelete();
            $table->string('faculty_name', 180);
            $table->unsignedInteger('student_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestampsTz();
            $table->index(['institution_annual_declaration_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_declaration_faculties');
    }
};
