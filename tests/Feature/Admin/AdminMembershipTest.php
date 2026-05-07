<?php

use App\Models\Member;

beforeEach(function () {
    ensureRole('admin');
});

it('lists members using member type author or publisher, not author sub-type', function () {
    $admin = actingAsApiUser('admin', ['account_type' => 'admin']);

    Member::factory()->create(['member_type' => 'author']);
    Member::factory()->create(['member_type' => 'publisher']);

    $response = $this->getJson('/api/v1/admin/members');

    $response->assertOk()
        ->assertJsonCount(2, 'data');

    $types = collect($response->json('data'))->pluck('member_type')->all();

    expect($types)->toContain('author')
        ->and($types)->toContain('publisher')
        ->and($types)->not->toContain('individual')
        ->and($types)->not->toContain('corporate')
        ->and($types)->not->toContain('agent');
});
