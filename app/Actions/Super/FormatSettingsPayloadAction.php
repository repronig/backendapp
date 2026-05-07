<?php

namespace App\Actions\Super;

use Illuminate\Support\Collection;

class FormatSettingsPayloadAction
{
    public function execute(Collection $settings): array
    {
        return [
            'app' => $settings->get('app', [
                'name' => 'REPRONIG',
                'env' => config('app.env'),
                'debug' => (bool) config('app.debug'),
                'timezone' => config('app.timezone'),
            ]),
            'membership' => $settings->get('membership', [
                'open_registration' => true,
                'require_association_selection' => true,
                'require_kyc' => true,
                'require_proof_of_address' => true,
                'accepted_id_types' => [
                    'proof_of_id',
                    'proof_of_address',
                ],
            ]),
            'licensing' => $this->normalizeLicensing($settings->get('licensing', [])),
            'documents' => $settings->get('documents', [
                'allowed_extensions' => [
                    'jpg',
                    'jpeg',
                    'png',
                    'pdf',
                ],
                'max_upload_size_mb' => 10,
                'private_storage' => true,
            ]),
            'notifications' => $settings->get('notifications', [
                'email_enabled' => true,
                'send_application_status_updates' => true,
                'send_payment_updates' => true,
                'send_usage_declaration_reminders' => true,
            ]),
            'security' => $settings->get('security', [
                'password_min_length' => 8,
                'force_https' => true,
                'audit_logging_enabled' => true,
            ]),
        ];
    }

    /**
     * @param  mixed  $raw
     */
    public function normalizeLicensing($raw): array
    {
        $stored = is_array($raw) ? $raw : [];
        $hasExplicitGatewayToggles = array_key_exists('paystack_enabled', $stored)
            || array_key_exists('flutterwave_enabled', $stored);

        $merged = array_replace_recursive($this->licensingDefaults(), $stored);

        if (! $hasExplicitGatewayToggles && isset($stored['payment_gateway'])) {
            $legacy = (string) $stored['payment_gateway'];
            $merged['paystack_enabled'] = $legacy === 'paystack';
            $merged['flutterwave_enabled'] = $legacy === 'flutterwave';
        }

        $merged['paystack_enabled'] = (bool) ($merged['paystack_enabled'] ?? true);
        $merged['flutterwave_enabled'] = (bool) ($merged['flutterwave_enabled'] ?? true);
        $merged['offline_payment_enabled'] = (bool) ($merged['offline_payment_enabled'] ?? true);

        $enabledOnline = [];
        if ($merged['paystack_enabled']) {
            $enabledOnline[] = 'paystack';
        }
        if ($merged['flutterwave_enabled']) {
            $enabledOnline[] = 'flutterwave';
        }

        if ($enabledOnline === []) {
            $merged['default_online_gateway'] = null;
        } else {
            $default = $merged['default_online_gateway'] ?? 'paystack';
            if (! in_array($default, ['paystack', 'flutterwave'], true)) {
                $default = 'paystack';
            }
            if (! in_array($default, $enabledOnline, true)) {
                $default = $enabledOnline[0];
            }
            $merged['default_online_gateway'] = $default;
        }

        return $merged;
    }

    private function licensingDefaults(): array
    {
        return [
            'allow_licence_application' => true,
            'blanket_annual_licensing' => true,
            'require_usage_declaration' => true,
            'default_currency' => 'NGN',
            'paystack_enabled' => true,
            'flutterwave_enabled' => true,
            'default_online_gateway' => 'paystack',
            'offline_payment_enabled' => true,
            'repronig_bank' => [
                'account_name' => '',
                'bank_name' => '',
                'account_number' => '',
                'reference_note' => '',
            ],
            'institution_licensing_terms' => [
                'version' => '1.0',
                'title' => 'Institutional licensing and payment obligations',
                'body' => '',
            ],
        ];
    }
}
