<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('export_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 80)->index();
            $table->string('mapping_key', 120);
            $table->json('mapping_json');
            $table->boolean('is_active')->default(true)->index();
            $table->timestampsTz();
            $table->unique(['domain', 'mapping_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_mappings');
    }
};
