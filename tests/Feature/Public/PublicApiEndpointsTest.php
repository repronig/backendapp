<?php

use App\Models\Institution;

it('exposes languages for unauthenticated clients', function () {
    $this->getJson('/api/v1/languages')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('exposes platform settings for unauthenticated clients', function () {
    $this->getJson('/api/v1/platform-settings')
        ->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => [
                'licensing' => [
                    'default_currency',
                    'paystack_enabled',
                    'flutterwave_enabled',
                    'offline_payment_enabled',
                    'enabled_online_gateways',
                    'default_online_gateway',
                    'repronig_bank' => [
                        'account_name',
                        'bank_name',
                        'account_number',
                        'reference_note',
                    ],
                    'institution_licensing_terms' => [
                        'version',
                        'title',
                        'body',
                    ],
                ],
            ],
        ]);
});

it('lists public associations', function () {
    $this->getJson('/api/v1/associations')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists location states', function () {
    $this->getJson('/api/v1/locations/states')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('returns a licence summary when the licence id matches an institution', function () {
    Institution::factory()->create([
        'licence_id' => 'E2E-PUBLIC-LIC-001',
        'account_status' => 'active',
    ]);

    $this->getJson('/api/v1/licensing/lookup/E2E-PUBLIC-LIC-001')
        ->assertOk()
        ->assertJsonPath('message', 'Licence summary retrieved successfully.');
});

it('returns not found for an unknown licence id on public lookup', function () {
    $this->getJson('/api/v1/licensing/lookup/UNKNOWN-LICENCE-ID-99999')
        ->assertNotFound();
});
