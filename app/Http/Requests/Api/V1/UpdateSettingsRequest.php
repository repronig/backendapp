<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app' => ['sometimes', 'array'],
            'membership' => ['sometimes', 'array'],
            'licensing' => ['sometimes', 'array'],
            'licensing.allow_licence_application' => ['sometimes', 'boolean'],
            'licensing.blanket_annual_licensing' => ['sometimes', 'boolean'],
            'licensing.require_usage_declaration' => ['sometimes', 'boolean'],
            'licensing.default_currency' => ['sometimes', 'string', 'max:12'],
            'licensing.paystack_enabled' => ['sometimes', 'boolean'],
            'licensing.flutterwave_enabled' => ['sometimes', 'boolean'],
            'licensing.default_online_gateway' => ['sometimes', 'nullable', 'in:paystack,flutterwave'],
            'licensing.offline_payment_enabled' => ['sometimes', 'boolean'],
            'licensing.repronig_bank' => ['sometimes', 'array'],
            'licensing.repronig_bank.account_name' => ['nullable', 'string', 'max:160'],
            'licensing.repronig_bank.bank_name' => ['nullable', 'string', 'max:160'],
            'licensing.repronig_bank.account_number' => ['nullable', 'string', 'max:64'],
            'licensing.repronig_bank.reference_note' => ['nullable', 'string', 'max:2000'],
            'licensing.institution_licensing_terms' => ['sometimes', 'array'],
            'licensing.institution_licensing_terms.version' => ['nullable', 'string', 'max:64'],
            'licensing.institution_licensing_terms.title' => ['nullable', 'string', 'max:200'],
            'licensing.institution_licensing_terms.body' => ['nullable', 'string', 'max:50000'],
            'licensing.payment_gateway' => ['sometimes', 'nullable', 'in:paystack,flutterwave'],
            'licensing.supported_gateways' => ['sometimes', 'array'],
            'licensing.supported_gateways.*' => ['in:paystack,flutterwave'],
            'documents' => ['sometimes', 'array'],
            'notifications' => ['sometimes', 'array'],
            'security' => ['sometimes', 'array'],
            'security.password_min_length' => ['sometimes', 'integer', 'min:6', 'max:64'],
            'security.force_https' => ['sometimes', 'boolean'],
            'security.audit_logging_enabled' => ['sometimes', 'boolean'],
            'security.otp_email_enabled' => ['sometimes', 'boolean'],
            'security.otp_sms_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $licensing = $this->input('licensing');
            if (! is_array($licensing)) {
                $security = $this->input('security');
                if (is_array($security)) {
                    $emailOtp = array_key_exists('otp_email_enabled', $security)
                        ? (bool) $security['otp_email_enabled']
                        : true;
                    $smsOtp = array_key_exists('otp_sms_enabled', $security)
                        ? (bool) $security['otp_sms_enabled']
                        : false;

                    if (! $emailOtp && ! $smsOtp) {
                        $validator->errors()->add(
                            'security.otp_email_enabled',
                            'Enable at least one OTP delivery channel (email or SMS).'
                        );
                    }
                }

                return;
            }

            $paystack = array_key_exists('paystack_enabled', $licensing)
                ? (bool) $licensing['paystack_enabled']
                : true;
            $flutterwave = array_key_exists('flutterwave_enabled', $licensing)
                ? (bool) $licensing['flutterwave_enabled']
                : true;

            $enabled = [];
            if ($paystack) {
                $enabled[] = 'paystack';
            }
            if ($flutterwave) {
                $enabled[] = 'flutterwave';
            }

            $default = $licensing['default_online_gateway'] ?? null;
            if ($default !== null && $default !== '' && $enabled !== [] && ! in_array($default, $enabled, true)) {
                $validator->errors()->add(
                    'licensing.default_online_gateway',
                    'The default online gateway must be one of the enabled gateways.'
                );
            }
            if (($default !== null && $default !== '') && $enabled === []) {
                $validator->errors()->add(
                    'licensing.default_online_gateway',
                    'Clear the default online gateway when all online payment gateways are disabled.'
                );
            }

            $security = $this->input('security');
            if (is_array($security)) {
                $emailOtp = array_key_exists('otp_email_enabled', $security)
                    ? (bool) $security['otp_email_enabled']
                    : true;
                $smsOtp = array_key_exists('otp_sms_enabled', $security)
                    ? (bool) $security['otp_sms_enabled']
                    : false;

                if (! $emailOtp && ! $smsOtp) {
                    $validator->errors()->add(
                        'security.otp_email_enabled',
                        'Enable at least one OTP delivery channel (email or SMS).'
                    );
                }
            }
        });
    }
}
