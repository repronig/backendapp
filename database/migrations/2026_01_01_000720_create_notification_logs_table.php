<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('notification_key', 120)->index();
            $table->string('channel', 50)->index();
            $table->string('status', 50)->default('queued')->index();
            $table->string('subject', 180)->nullable();
            $table->json('payload_json')->nullable();
            $table->timestampTz('sent_at')->nullable();
            $table->timestampTz('failed_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestampsTz();
            $table->index(['channel', 'status']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE notification_logs ADD CONSTRAINT notification_logs_channel_check CHECK (channel IN ('email', 'sms', 'system'))");
            DB::statement("ALTER TABLE notification_logs ADD CONSTRAINT notification_logs_status_check CHECK (status IN ('queued','sent','failed','skipped'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
