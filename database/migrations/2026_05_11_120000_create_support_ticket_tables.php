<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('portal_context', 32);
            $table->string('subject');
            $table->text('body');
            $table->string('category', 64);
            $table->string('status', 32)->default('open');
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('status');
        });

        Schema::create('support_ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->boolean('is_staff')->default(false);
            $table->timestamps();

            $table->index('support_ticket_id');
        });

        Schema::create('support_ticket_internal_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained('support_tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index('support_ticket_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_ticket_internal_notes');
        Schema::dropIfExists('support_ticket_replies');
        Schema::dropIfExists('support_tickets');
    }
};
