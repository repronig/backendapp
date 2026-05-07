<?php

use App\Models\Institution;
use App\Models\Member;

beforeEach(function () {
    ensureRole('super_admin');
});

it('keeps institution data separate from member data for super admin institution management', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $institution = Institution::factory()->create([
        'name' => 'University of REPRONIG',
        'email' => 'registrar@university.test',
    ]);
    Member::factory()->create(['member_type' => 'author']);

    $response = $this->getJson('/api/v1/admin/institutions');

    $response->assertOk();

    $names = collect($response->json('data'))->pluck('name')->all();

    expect($names)->toContain('University of REPRONIG');
    expect($response->json('data.0'))->toHaveKey('account_status');
    expect($response->json('data.0'))->not->toHaveKey('member_type');
});
