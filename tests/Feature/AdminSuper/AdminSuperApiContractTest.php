<?php

beforeEach(function () {
    ensureRole('admin');
    ensureRole('super_admin');
    ensureRole('member');
});

it('keeps admin dashboard summary envelope stable', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('message', 'Admin dashboard summary retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data' => ['meta', 'users', 'recent_activity'],
        ]);
});

it('keeps admin member-applications list envelope and pagination contract stable', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/member-applications?per_page=20')
        ->assertOk()
        ->assertJsonPath('message', 'Member applications retrieved successfully.')
        ->assertJsonPath('meta.per_page', 20)
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('keeps super dashboard summary envelope stable', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('message', 'Super admin dashboard summary retrieved successfully.')
        ->assertJsonStructure([
            'message',
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

it('keeps super integrations outbox summary envelope stable', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/integrations/outbox/summary')
        ->assertOk()
        ->assertJsonPath('message', 'Integration outbox summary retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data' => ['pending_total', 'processing_total', 'failed_last_24h', 'oldest_pending_created_at', 'oldest_pending_scheduled_at'],
        ]);
});

it('keeps super integrations outbox list envelope and pagination contract stable', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/super/integrations/outbox?per_page=10')
        ->assertOk()
        ->assertJsonPath('message', 'Integration outbox retrieved successfully.')
        ->assertJsonPath('meta.per_page', 10)
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});
