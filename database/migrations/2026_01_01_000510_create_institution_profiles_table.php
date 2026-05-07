<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('institution_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('academic_staff_count')->nullable();
            $table->unsignedInteger('administrative_staff_count')->nullable();
            $table->unsignedInteger('campuses_count')->nullable();
            $table->json('metadata_json')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_profiles');
    }
};
