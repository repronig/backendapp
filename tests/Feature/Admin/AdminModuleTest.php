<?php

use App\Models\Member;
use App\Models\Work;

beforeEach(function () {
    ensureRole('admin');
    ensureRole('super_admin');
    ensureRole('member');
});

it('returns admin dashboard summary for admin users', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/dashboard/summary')
        ->assertOk()
        ->assertJsonPath('message', 'Admin dashboard summary retrieved successfully.')
        ->assertJsonStructure(['data' => ['meta', 'users', 'recent_activity']]);
});

it('returns admin dashboard summary for super admin users', function () {
    actingAsApiUser('super_admin', ['account_type' => 'super_admin']);

    $this->getJson('/api/v1/admin/dashboard/summary')
        ->assertOk();
});

it('denies admin dashboard summary to members', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->getJson('/api/v1/admin/dashboard/summary')->assertForbidden();
});

it('denies unauthenticated access to admin dashboard summary', function () {
    $this->getJson('/api/v1/admin/dashboard/summary')->assertUnauthorized();
});

it('denies admin works index to members', function () {
    actingAsApiUser('member', ['account_type' => 'member']);

    $this->getJson('/api/v1/admin/works')->assertForbidden();
});

it('lists admin works for admin users', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $member = Member::factory()->create(['approval_status' => 'approved']);
    Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'submitted',
        'verification_status' => 'pending',
    ]);

    $this->getJson('/api/v1/admin/works?status=submitted')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('shows a single admin work', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $member = Member::factory()->create();
    $work = Work::factory()->create([
        'member_id' => $member->id,
        'title' => 'Admin module visibility work',
    ]);

    $this->getJson("/api/v1/admin/works/{$work->id}")
        ->assertOk()
        ->assertJsonPath('data.title', 'Admin module visibility work');
});

it('lists admin member applications', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/member-applications')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists admin institutions', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/institutions')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists admin licences', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/licences')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('returns admin finance summary', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/finance/summary')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('returns admin member report', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/reports/members')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('returns admin work report', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/reports/works')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('lists admin audit logs', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/audit-logs')
        ->assertOk()
        ->assertJsonStructure(['message', 'data', 'meta']);
});

it('lists admin licensing fee plans', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/licensing/fee-plans')
        ->assertOk()
        ->assertJsonStructure(['message', 'data']);
});

it('accepts per_page 10 20 50 100 on admin members index', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    Member::factory()->count(3)->create(['approval_status' => 'approved']);

    $this->getJson('/api/v1/admin/members?per_page=50')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 50);

    $this->getJson('/api/v1/admin/members?per_page=37')
        ->assertOk()
        ->assertJsonPath('meta.per_page', 15);
});
