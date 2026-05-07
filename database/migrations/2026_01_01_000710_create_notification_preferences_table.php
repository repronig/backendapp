<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('channel', 50)->index();
            $table->string('notification_key', 120)->index();
            $table->boolean('is_enabled')->default(true);
            $table->timestampsTz();
            $table->unique(['user_id', 'channel', 'notification_key']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE notification_preferences ADD CONSTRAINT notification_preferences_channel_check CHECK (channel IN ('email','sms','system'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
