<?php

namespace App\Services\Integrations;

use App\Contracts\DeliversIntegrationOutboxEntry;
use App\Enums\IntegrationEnvironment;
use App\Enums\IntegrationProvider;
use App\Models\ExternalIntegration;
use App\Models\IntegrationOutboxEntry;
use App\Services\Integrations\WipoConnect\WipoConnectOutboundPayloadBuilder;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WipoConnectHttpDeliverer implements DeliversIntegrationOutboxEntry
{
    public function __construct(
        protected WipoConnectOutboundPayloadBuilder $payloadBuilder
    ) {}

    public function deliver(IntegrationOutboxEntry $entry): void
    {
        if ($entry->provider !== IntegrationProvider::WipoConnect) {
            throw new \InvalidArgumentException(
                'WipoConnectHttpDeliverer only supports provider '.$entry->provider->value.'.'
            );
        }

        $integration = $this->resolveIntegration($entry);
        $config = is_array($integration->config) ? $integration->config : [];

        $url = $this->resolveSyncUrl($config);
        $method = strtoupper((string) ($config['sync_http_method'] ?? 'POST'));
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH'], true)) {
            throw new \InvalidArgumentException('Invalid sync_http_method; use GET, POST, PUT, or PATCH.');
        }

        $token = $this->resolveAccessToken($config);
        $body = $this->payloadBuilder->build($entry);

        $timeout = (int) config('integrations.wipo_connect.http_timeout_seconds', 30);

        $request = Http::timeout($timeout)
            ->acceptJson()
            ->asJson();

        if ($token !== null && $token !== '') {
            $request = $request->withToken($token);
        }

        $options = $method === 'GET'
            ? ['query' => $body]
            : ['json' => $body];

        $response = $request->send($method, $url, $options);

        if ($response->failed()) {
            try {
                $response->throw();
            } catch (RequestException $e) {
                $summary = Str::limit((string) $response->body(), 500);

                throw new \RuntimeException(
                    'WIPO Connect HTTP delivery failed ('.$response->status().'): '.$summary,
                    0,
                    $e
                );
            }
        }
    }

    private function resolveIntegration(IntegrationOutboxEntry $entry): ExternalIntegration
    {
        $environment = null;
        $envRaw = is_array($entry->payload) ? ($entry->payload['environment'] ?? null) : null;
        if (is_string($envRaw) && $envRaw !== '') {
            $environment = IntegrationEnvironment::tryFrom($envRaw);
        }

        $query = ExternalIntegration::query()
            ->where('provider', IntegrationProvider::WipoConnect)
            ->where('is_enabled', true);

        if ($environment !== null) {
            $query->where('environment', $environment);
        }

        $integration = $query
            ->orderByRaw('CASE WHEN environment = ? THEN 0 ELSE 1 END', [IntegrationEnvironment::Sandbox->value])
            ->first();

        if ($integration === null) {
            throw new \RuntimeException('No enabled WIPO Connect integration matches this outbox entry.');
        }

        return $integration;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveSyncUrl(array $config): string
    {
        $syncUrl = isset($config['sync_url']) ? trim((string) $config['sync_url']) : '';

        if ($syncUrl !== '' && filter_var($syncUrl, FILTER_VALIDATE_URL)) {
            return $syncUrl;
        }

        $base = isset($config['api_base_url']) ? rtrim(trim((string) $config['api_base_url']), '/') : '';
        $path = isset($config['sync_path']) ? trim((string) $config['sync_path']) : '';

        if ($base === '' || $path === '' || ! str_starts_with($path, '/')) {
            throw new \InvalidArgumentException(
                'WIPO Connect HTTP delivery requires sync_url (absolute) or api_base_url plus sync_path (starting with "/").'
            );
        }

        return $base.$path;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveAccessToken(array $config): ?string
    {
        $bearer = isset($config['bearer_token']) ? trim((string) $config['bearer_token']) : '';
        if ($bearer !== '') {
            return $bearer;
        }

        $clientId = isset($config['client_id']) ? trim((string) $config['client_id']) : '';
        $clientSecret = isset($config['client_secret']) ? trim((string) $config['client_secret']) : '';
        $tokenUrl = isset($config['oauth_token_url']) ? trim((string) $config['oauth_token_url']) : '';

        if ($clientId === '' || $clientSecret === '' || $tokenUrl === '') {
            throw new \InvalidArgumentException(
                'WIPO Connect HTTP delivery requires bearer_token or oauth_token_url with client_id and client_secret.'
            );
        }

        $scope = isset($config['oauth_scope']) ? trim((string) $config['oauth_scope']) : '';

        $timeout = (int) config('integrations.wipo_connect.http_timeout_seconds', 30);

        $response = Http::timeout($timeout)
            ->asForm()
            ->acceptJson()
            ->post($tokenUrl, array_filter([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => $scope !== '' ? $scope : null,
            ]));

        if ($response->failed()) {
            throw new \RuntimeException(
                'WIPO Connect OAuth token request failed ('.$response->status().'): '.Str::limit((string) $response->body(), 400)
            );
        }

        $token = $response->json('access_token');

        if (! is_string($token) || $token === '') {
            throw new \RuntimeException('WIPO Connect OAuth response did not include access_token.');
        }

        return $token;
    }
}
