<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('code', 10)->unique();
            $table->string('country_code', 2)->default('NG')->index();
            $table->timestampsTz();
            $table->unique(['country_code', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
