<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class PlatformSettingsSeeder extends Seeder
{
    /** Version string for seeded institution_licensing_terms; demo institutions are pre-accepted to match. */
    public const DEMO_INSTITUTION_LICENSING_TERMS_VERSION = '2026';

    public function run(): void
    {
        $settings = [
            ['group' => 'platform', 'key' => 'app_name', 'value' => 'REPRONIG Digital Rights Management Platform'],
            ['group' => 'payments', 'key' => 'default_currency', 'value' => 'NGN'],
            [
                'group' => 'general',
                'key' => 'licensing',
                'value' => [
                    'default_invoice_due_days' => 30,
                    'paystack_enabled' => true,
                    'flutterwave_enabled' => true,
                    'default_online_gateway' => 'paystack',
                    'offline_payment_enabled' => true,
                    'repronig_bank' => [
                        'account_name' => 'REPRONIG',
                        'bank_name' => 'WEMA Bank',
                        'account_number' => '0001234567',
                        'reference_note' => 'Use your invoice number as narration.',
                    ],
                    'institution_licensing_terms' => [
                        'version' => self::DEMO_INSTITUTION_LICENSING_TERMS_VERSION,
                        'title' => 'Institutional Licensing and Payment Obligations',
                        'body' => 'As an institution, you are required to comply with all applicable copyright and collective management obligations, pay assessed licence fees when due, and ensure that all declaration information remains accurate and up to date. By continuing to use this portal, you confirm that you understand and accept these obligations.',
                    ],
                ],
            ],
        ];

        foreach ($settings as $setting) {
            Setting::query()->updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
