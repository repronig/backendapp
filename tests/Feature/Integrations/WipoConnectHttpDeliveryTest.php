<?php

use App\Actions\Integrations\ProcessPendingIntegrationOutboxAction;
use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Enums\IntegrationSyncStatus;
use App\Models\ExternalIntegration;
use App\Models\IntegrationOutboxEntry;
use App\Models\Work;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

afterEach(function () {
    Carbon::setTestNow();
    Config::set('integrations.wipo_connect.delivery', 'stub');
    Config::set('integrations.outbox.max_attempts', 5);
});

it('delivers WIPO Connect outbox rows over HTTP when delivery mode is http', function () {
    Config::set('integrations.wipo_connect.delivery', 'http');

    Http::fake(function (Request $request) {
        if (str_contains($request->url(), 'auth.wipo.test')) {
            return Http::response(['access_token' => 'oauth-token', 'expires_in' => 3600], 200);
        }

        if (str_contains($request->url(), 'wipo.test')) {
            return Http::response(['accepted' => true], 200);
        }

        return Http::response('unexpected', 500);
    });

    ExternalIntegration::factory()->create([
        'environment' => IntegrationEnvironment::Sandbox,
        'is_enabled' => true,
        'config' => [
            'api_base_url' => 'https://wipo.test',
            'sync_path' => '/api/v1/ingest',
            'oauth_token_url' => 'https://auth.wipo.test/token',
            'client_id' => 'repronig-client',
            'client_secret' => 'repronig-secret',
            'oauth_scope' => 'ingest.write',
            'sync_http_method' => 'POST',
        ],
    ]);

    $work = Work::factory()->create(['title' => 'Rights exchange sample']);

    $entry = IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $work->getMorphClass(),
        'subject_id' => $work->id,
        'operation' => 'sync_work',
        'payload' => ['environment' => IntegrationEnvironment::Sandbox->value],
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $processed = app(ProcessPendingIntegrationOutboxAction::class)->execute(10);
    expect($processed)->toBe(1);

    expect($entry->fresh()->status)->toBe(IntegrationSyncStatus::Succeeded);

    Http::assertSent(function (Request $request) {
        return str_contains($request->url(), 'auth.wipo.test/token')
            && $request->data()['grant_type'] === 'client_credentials';
    });

    Http::assertSent(function (Request $request) use ($work, $entry) {
        if (! str_contains($request->url(), 'wipo.test/api/v1/ingest')) {
            return false;
        }

        $data = $request->data();

        return ($data['repronig']['outbox_id'] ?? null) === $entry->id
            && ($data['subject']['title'] ?? null) === 'Rights exchange sample'
            && ($data['subject']['id'] ?? null) === $work->id
            && ($data['wipo_connect']['operation'] ?? null) === 'sync_work'
            && ($data['wipo_connect']['work']['title'] ?? null) === 'Rights exchange sample';
    });
});

it('marks outbox failed when WIPO Connect HTTP returns an error status', function () {
    Config::set('integrations.wipo_connect.delivery', 'http');
    Config::set('integrations.outbox.max_attempts', 1);

    Http::fake([
        'https://wipo.test/hook' => Http::response(['error' => 'bad request'], 422),
    ]);

    ExternalIntegration::factory()->create([
        'is_enabled' => true,
        'config' => [
            'sync_url' => 'https://wipo.test/hook',
            'bearer_token' => 'static-bearer',
        ],
    ]);

    $work = Work::factory()->create();

    $entry = IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $work->getMorphClass(),
        'subject_id' => $work->id,
        'payload' => ['environment' => IntegrationEnvironment::Sandbox->value],
        'status' => IntegrationSyncStatus::Pending,
    ]);

    app(ProcessPendingIntegrationOutboxAction::class)->execute(10);

    $entry->refresh();
    expect($entry->status)->toBe(IntegrationSyncStatus::Failed)
        ->and($entry->last_error)->toContain('422');
});

it('requeues failed deliveries with backoff until max attempts', function () {
    Config::set('integrations.wipo_connect.delivery', 'http');
    Config::set('integrations.outbox.max_attempts', 2);
    Config::set('integrations.outbox.retry_backoff_base_seconds', 120);
    Config::set('integrations.outbox.retry_backoff_max_seconds', 600);

    Http::fake([
        'https://wipo.test/hook' => Http::response('server error', 500),
    ]);

    ExternalIntegration::factory()->create([
        'is_enabled' => true,
        'config' => [
            'sync_url' => 'https://wipo.test/hook',
            'bearer_token' => 'static-bearer',
        ],
    ]);

    $work = Work::factory()->create();

    $entry = IntegrationOutboxEntry::factory()->create([
        'provider' => IntegrationProvider::WipoConnect,
        'subject_type' => $work->getMorphClass(),
        'subject_id' => $work->id,
        'payload' => ['environment' => IntegrationEnvironment::Sandbox->value],
        'status' => IntegrationSyncStatus::Pending,
    ]);

    $processor = app(ProcessPendingIntegrationOutboxAction::class);

    $processor->execute(10);
    $entry->refresh();

    expect($entry->status)->toBe(IntegrationSyncStatus::Pending)
        ->and($entry->attempts)->toBe(1)
        ->and($entry->scheduled_at)->not->toBeNull()
        ->and($entry->last_error)->toContain('500');

    Carbon::setTestNow($entry->scheduled_at->copy()->addSecond());

    $processor->execute(10);
    $entry->refresh();

    expect($entry->status)->toBe(IntegrationSyncStatus::Failed)
        ->and($entry->attempts)->toBe(2);
});
