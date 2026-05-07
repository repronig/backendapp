<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('associations', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->string('name', 180);
            $table->string('code', 40)->unique();
            $table->string('type', 50)->index();
            $table->text('description')->nullable();
            $table->string('contact_email')->nullable()->index();
            $table->string('contact_phone', 30)->nullable();
            $table->string('status', 50)->default('active')->index();
            $table->string('logo_path')->nullable();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->string('country', 120)->default('Nigeria');
            $table->string('postal_code', 30)->nullable();
            $table->boolean('is_enabled')->default(true)->index();
            $table->timestampTz('disabled_at')->nullable();
            $table->foreignId('disabled_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disable_reason', 120)->nullable();
            $table->timestampsTz();
            $table->index(['status', 'is_enabled']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE associations ADD CONSTRAINT associations_status_check CHECK (status IN ('active','inactive','disabled'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('associations');
    }
};
