<?php

namespace App\Support\Payments;

use App\Actions\Super\FormatSettingsPayloadAction;
use App\Actions\Super\GetSettingsAction;
use Illuminate\Validation\ValidationException;

class PaymentGatewaySettings
{
    public function __construct(
        protected GetSettingsAction $getSettingsAction,
        protected FormatSettingsPayloadAction $formatter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function licensing(): array
    {
        $settings = $this->formatter->execute($this->getSettingsAction->execute());

        return $settings['licensing'] ?? [];
    }

    /**
     * Online PSP gateways currently allowed for institution / licence checkout.
     *
     * @return list<string>
     */
    public function enabledOnlineGateways(): array
    {
        $licensing = $this->licensing();
        $out = [];
        if (! empty($licensing['paystack_enabled'])) {
            $out[] = 'paystack';
        }
        if (! empty($licensing['flutterwave_enabled'])) {
            $out[] = 'flutterwave';
        }

        return $out;
    }

    public function offlinePaymentsEnabled(): bool
    {
        return (bool) ($this->licensing()['offline_payment_enabled'] ?? true);
    }

    /**
     * Preferred default when opening a payment form. Null when no online PSP is enabled.
     */
    public function defaultOnlineGateway(): ?string
    {
        $licensing = $this->licensing();
        $enabled = $this->enabledOnlineGateways();
        if ($enabled === []) {
            return null;
        }

        $default = $licensing['default_online_gateway'] ?? null;
        if (is_string($default) && in_array($default, $enabled, true)) {
            return $default;
        }

        return $enabled[0];
    }

    /**
     * @deprecated Use defaultOnlineGateway() or enabledOnlineGateways().
     */
    public function activeGateway(): ?string
    {
        return $this->defaultOnlineGateway();
    }

    public function assertOnlineGatewayEnabled(string $gateway): void
    {
        if (! in_array($gateway, ['paystack', 'flutterwave'], true)) {
            throw ValidationException::withMessages([
                'gateway_name' => ['Unsupported payment gateway for online checkout.'],
            ]);
        }

        if (! in_array($gateway, $this->enabledOnlineGateways(), true)) {
            throw ValidationException::withMessages([
                'gateway_name' => ["{$gateway} is disabled for institution payments."],
            ]);
        }
    }

    /**
     * @deprecated Use assertOnlineGatewayEnabled()
     */
    public function assertGatewayIsActive(string $gateway): void
    {
        $this->assertOnlineGatewayEnabled($gateway);
    }

    /**
     * Published institution licensing terms body (non-empty) means first-login acceptance is meaningful.
     *
     * @return array{version: string, title: string, body: string}|null
     */
    public function configuredInstitutionLicensingTerms(): ?array
    {
        $terms = $this->licensing()['institution_licensing_terms'] ?? [];
        $body = trim((string) ($terms['body'] ?? ''));
        if ($body === '') {
            return null;
        }

        return [
            'version' => (string) ($terms['version'] ?? '1.0'),
            'title' => (string) ($terms['title'] ?? ''),
            'body' => $body,
        ];
    }
}
