<?php

use App\Models\AuditLog;
use App\Models\Member;
use App\Models\Work;
use App\Support\DashboardPayload;

beforeEach(function () {
    ensureRole('member');
});

it('denies member routes to guests', function () {
    $this->getJson('/api/v1/works')->assertUnauthorized();
    $this->getJson('/api/v1/member/profile')->assertUnauthorized();
});

it('requires authentication for the me endpoint', function () {
    $this->getJson('/api/v1/me')->assertUnauthorized();
});

it('returns the current user context for an authenticated member', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->getJson('/api/v1/me')
        ->assertOk()
        ->assertJsonPath('message', 'Authenticated user retrieved successfully.')
        ->assertJsonStructure(['data' => ['user']]);
});

it('lists works for an approved member', function () {
    [, $member] = actingAsApprovedMember();

    Work::factory()->create([
        'member_id' => $member->id,
        'title' => 'Portal module work',
        'work_status' => 'draft',
    ]);

    $this->getJson('/api/v1/works')
        ->assertOk()
        ->assertJsonPath('message', 'Works retrieved successfully.')
        ->assertJsonPath('meta.total', 1);
});

it('retrieves member profile for an approved member', function () {
    [$user] = actingAsApprovedMember();

    $this->getJson('/api/v1/member/profile')
        ->assertOk()
        ->assertJsonPath('message', 'Member profile retrieved successfully.')
        ->assertJsonStructure(['data' => ['member_id', 'member_type', 'member_provided_id', 'user']])
        ->assertJsonPath('data.user.first_name', $user->first_name)
        ->assertJsonPath('data.user.last_name', $user->last_name);
});

it('allows an approved member to update member_provided_id via profile', function () {
    [, $member] = actingAsApprovedMember();

    $this->patchJson('/api/v1/member/profile', [
        'member_provided_id' => 'UPDATED-ID-42',
    ])
        ->assertOk()
        ->assertJsonPath('data.member_provided_id', 'UPDATED-ID-42');

    expect($member->fresh()->member_provided_id)->toBe('UPDATED-ID-42');
});

it('returns member dashboard summary', function () {
    actingAsApprovedMember();

    $this->getJson('/api/v1/me/dashboard-summary')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('exposes dashboard meta and includes member audit rows matched by model class', function () {
    [$user, $member] = actingAsApprovedMember();

    AuditLog::factory()->create([
        'actor_user_id' => $user->id,
        'action' => 'member_profile_updated',
        'subject_type' => Member::class,
        'subject_id' => $member->id,
    ]);

    $this->getJson('/api/v1/me/dashboard-summary')
        ->assertOk()
        ->assertJsonPath('data.meta.schema_version', DashboardPayload::SCHEMA_VERSION)
        ->assertJsonPath('data.member.recent_activity.0.subject_type', 'Member')
        ->assertJsonPath('data.member.recent_activity.0.action', 'member_profile_updated');
});

it('returns notification unread count', function () {
    actingAsApprovedMember();

    $this->getJson('/api/v1/me/notifications/unread-count')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});
