<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('association_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('association_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('designation_title', 150)->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestampsTz();
            $table->unique(['association_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('association_user');
    }
};
