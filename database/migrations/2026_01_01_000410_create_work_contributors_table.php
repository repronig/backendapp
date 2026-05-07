<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('work_contributors', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->unique();
            $table->foreignId('work_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contributor_name', 180);
            $table->string('contributor_role', 80)->index();
            $table->string('right_type', 50)->index();
            $table->decimal('ownership_percentage', 5, 2)->default(0);
            $table->boolean('is_disputed')->default(false)->index();
            $table->string('dispute_reason_code', 50)->nullable();
            $table->text('dispute_reason')->nullable();
            $table->foreignId('disputed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampTz('disputed_at')->nullable();
            $table->string('territory_scope', 80)->nullable();
            $table->timestampsTz();
            $table->index(['work_id', 'contributor_role']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE work_contributors ADD CONSTRAINT work_contributors_right_type_check CHECK (right_type IN ('exclusive','non_exclusive','shared','assigned'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('work_contributors');
    }
};
