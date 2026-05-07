<?php

namespace App\Http\Controllers\Api\V1\Webhooks;

use App\Actions\Integrations\ProcessWipoConnectInboundWebhookAction;
use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Api\V1\WipoConnectInboundWebhookRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Inbound WIPO Connect (or broker) callbacks.
 *
 * JSON body (required): idempotency_key, environment (sandbox|production), event (succeeded|failed|acknowledged), optional outbox_id, optional message.
 *
 * Authentication (choose one mode via config):
 * - Default: header {@see X-Repronig-Webhook-Secret} must match the encrypted webhook_secret on the matching integration row.
 * - HMAC mode (WIPO_CONNECT_WEBHOOK_REQUIRE_HMAC=true): header X-Repronig-Signature must equal hex-encoded HMAC-SHA256 of the raw request body using the same webhook_secret.
 *
 * Optional IP allowlist: WIPO_CONNECT_WEBHOOK_ALLOWED_IPS (comma-separated); when empty, all IPs are accepted.
 */
class WipoConnectWebhookController extends BaseApiController
{
    public function handle(
        Request $request,
        ProcessWipoConnectInboundWebhookAction $action
    ): JsonResponse {
        $allowedIps = config('integrations.wipo_connect.webhook_allowed_ips', []);

        if ($allowedIps !== [] && ! in_array($request->ip(), $allowedIps, true)) {
            return $this->error('This IP is not allowed to call this webhook.', 403);
        }

        $raw = $request->getContent();
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            return $this->error('Invalid JSON payload.', 422);
        }

        try {
            Validator::make($decoded, WipoConnectInboundWebhookRequest::ruleset())->validate();
        } catch (ValidationException $e) {
            return $this->error('The given data was invalid.', 422, $e->errors());
        }

        $plainSecret = (string) $request->header('X-Repronig-Webhook-Secret', '');
        $signature = (string) $request->header('X-Repronig-Signature', '');

        try {
            $result = $action->execute($decoded, $plainSecret !== '' ? $plainSecret : null, $raw, $signature !== '' ? $signature : null);
        } catch (AccessDeniedHttpException $e) {
            return $this->error($e->getMessage(), 401);
        }

        if ($result['duplicate']) {
            return $this->success('Webhook event already processed.', [
                'duplicate' => true,
                'outbox_updated' => false,
            ]);
        }

        return $this->success('Webhook accepted.', [
            'duplicate' => false,
            'outbox_updated' => $result['outbox_updated'],
        ]);
    }
}
