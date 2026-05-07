<?php

beforeEach(function () {
    ensureRole('member');
});

it('returns two-factor status for an authenticated user', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->getJson('/api/v1/me/two-factor')
        ->assertOk()
        ->assertJsonPath('message', 'Two-factor status retrieved successfully.')
        ->assertJsonStructure(['data' => ['requires_two_factor', 'two_factor_confirmed_at']]);
});

it('requires authentication for two-factor status', function () {
    $this->getJson('/api/v1/me/two-factor')->assertUnauthorized();
});
