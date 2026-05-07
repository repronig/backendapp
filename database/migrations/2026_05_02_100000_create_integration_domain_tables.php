<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrations domain: external integrations, outbox, automations, compliance assessments, webhook idempotency.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('external_integrations', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index();
            $table->string('environment', 20)->index();
            $table->json('config')->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->text('webhook_secret')->nullable();
            $table->timestampsTz();

            $table->unique(['provider', 'environment']);
        });

        Schema::create('integration_outbox', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index();
            $table->nullableMorphs('subject');
            $table->string('operation', 120)->index();
            $table->json('payload')->nullable();
            $table->string('status', 30)->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestampTz('scheduled_at')->nullable()->index();
            $table->timestampTz('processed_at')->nullable();
            $table->timestampsTz();

            $table->index(['status', 'scheduled_at']);
        });

        Schema::create('automation_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->string('trigger', 30)->index();
            $table->string('cron', 120)->nullable();
            $table->boolean('is_enabled')->default(false)->index();
            $table->json('config')->nullable();
            $table->timestampsTz();
        });

        Schema::create('institution_compliance_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
            $table->timestampTz('assessed_at')->index();
            $table->string('assessment_type', 40)->index();
            $table->foreignId('source_declaration_id')->nullable()->constrained('institution_annual_declarations')->nullOnDelete();
            $table->json('scores')->nullable();
            $table->json('flags')->nullable();
            $table->string('overall_status', 20)->index();
            $table->timestampsTz();

            $table->index(['institution_id', 'assessed_at']);
        });

        Schema::create('integration_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 50)->index();
            $table->string('idempotency_key', 190)->unique();
            $table->json('payload')->nullable();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_webhook_events');
        Schema::dropIfExists('institution_compliance_assessments');
        Schema::dropIfExists('automation_definitions');
        Schema::dropIfExists('integration_outbox');
        Schema::dropIfExists('external_integrations');
    }
};
