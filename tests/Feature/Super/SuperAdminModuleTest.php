<?php

use Spatie\Permission\Models\Role;

beforeEach(function () {
    ensureRole('super_admin');
    ensureRole('admin');
});

it('returns super admin dashboard summary for super admins', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('message', 'Super admin dashboard summary retrieved successfully.')
        ->assertJsonStructure([
            'data' => [
                'meta',
                'users',
                'associations',
                'organizations',
                'members',
                'invoices',
                'payments',
                'roles',
                'revenue',
                'recent_activity',
            ],
        ]);
});

it('denies super dashboard summary to admins without super role', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/super/dashboard/summary')->assertForbidden();
});

it('denies unauthenticated access to super dashboard summary', function () {
    $this->getJson('/api/v1/super/dashboard/summary')->assertUnauthorized();
});

it('lists roles for super admins', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/roles')
        ->assertOk()
        ->assertJsonPath('message', 'Roles retrieved successfully.')
        ->assertJsonStructure(['data']);
});

it('denies roles list to non-super admins', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/super/roles')->assertForbidden();
});

it('denies super users index to admins', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/super/users')->assertForbidden();
});

it('lists super admin users', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/users')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists super admin managed associations', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/associations')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('returns super settings index', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/settings')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('updates licensing payment toggles and bank details for super admins', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $licensing = [
        'allow_licence_application' => true,
        'blanket_annual_licensing' => true,
        'require_usage_declaration' => true,
        'default_currency' => 'NGN',
        'paystack_enabled' => false,
        'flutterwave_enabled' => true,
        'default_online_gateway' => 'flutterwave',
        'offline_payment_enabled' => true,
        'repronig_bank' => [
            'account_name' => 'REPRONIG Collections',
            'bank_name' => 'GTBank',
            'account_number' => '0123456789',
            'reference_note' => 'Quote your invoice number.',
        ],
        'institution_licensing_terms' => [
            'version' => '2026-1',
            'title' => 'Institutional licensing',
            'body' => 'Sample obligation text for institutions.',
        ],
    ];

    $this->putJson('/api/v1/super/settings', ['licensing' => $licensing])
        ->assertOk()
        ->assertJsonPath('data.licensing.paystack_enabled', false)
        ->assertJsonPath('data.licensing.flutterwave_enabled', true)
        ->assertJsonPath('data.licensing.repronig_bank.account_number', '0123456789')
        ->assertJsonPath('data.licensing.institution_licensing_terms.version', '2026-1');

    $this->assertDatabaseHas('settings', [
        'group' => 'general',
        'key' => 'licensing',
    ]);

    $this->getJson('/api/v1/platform-settings')
        ->assertOk()
        ->assertJsonPath('data.licensing.paystack_enabled', false)
        ->assertJsonPath('data.licensing.enabled_online_gateways', ['flutterwave']);
});

it('rejects default online gateway that is not enabled', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->putJson('/api/v1/super/settings', [
        'licensing' => [
            'allow_licence_application' => true,
            'blanket_annual_licensing' => true,
            'require_usage_declaration' => true,
            'default_currency' => 'NGN',
            'paystack_enabled' => false,
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
                'title' => '',
                'body' => '',
            ],
        ],
    ])->assertUnprocessable();
});

it('lists super permissions', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/permissions')
        ->assertOk()
        ->assertJsonPath('message', 'Permissions retrieved successfully.')
        ->assertJsonStructure(['data']);
});

it('shows a single role by id for super admins', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $role = Role::findOrCreate('member', 'web');

    $this->getJson("/api/v1/super/roles/{$role->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Role retrieved successfully.')
        ->assertJsonStructure(['data']);
});

it('lists super-managed languages', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/languages')
        ->assertOk()
        ->assertJsonPath('message', 'Languages retrieved successfully.')
        ->assertJsonStructure(['data']);
});

it('lists integration configurations for super admins', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/integrations')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});
