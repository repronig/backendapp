<?php

use App\Models\MemberApplication;
use App\Models\Work;

beforeEach(function () {
    ensureRole('member');
});

it('keeps /me/dashboard-summary envelope stable for authenticated members', function () {
    actingAsApprovedMember();

    $this->getJson('/api/v1/me/dashboard-summary')
        ->assertOk()
        ->assertJsonPath('message', 'Dashboard summary retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data' => [
                'meta',
                'member',
            ],
        ]);
});

it('keeps member application workspace envelope stable', function () {
    [$user] = actingAsApprovedMember();

    MemberApplication::factory()->create([
        'user_id' => $user->id,
        'application_status' => 'draft',
    ]);

    $this->getJson('/api/v1/member-applications/me')
        ->assertOk()
        ->assertJsonPath('message', 'Member application retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data',
        ]);
});

it('keeps works list envelope and pagination contract stable', function () {
    [, $member] = actingAsApprovedMember();

    Work::factory()->create([
        'member_id' => $member->id,
        'title' => 'Contract Work A',
        'work_status' => 'draft',
    ]);

    $this->getJson('/api/v1/works?per_page=15')
        ->assertOk()
        ->assertJsonPath('message', 'Works retrieved successfully.')
        ->assertJsonPath('meta.per_page', 15)
        ->assertJsonStructure([
            'message',
            'data',
            'meta' => ['current_page', 'per_page', 'total', 'last_page', 'from', 'to'],
            'links' => ['first', 'last', 'prev', 'next'],
        ]);
});

it('keeps work detail envelope stable', function () {
    [, $member] = actingAsApprovedMember();
    $work = Work::factory()->create([
        'member_id' => $member->id,
        'work_status' => 'draft',
    ]);

    $this->getJson("/api/v1/works/{$work->id}")
        ->assertOk()
        ->assertJsonPath('message', 'Work retrieved successfully.')
        ->assertJsonStructure([
            'message',
            'data' => [
                'id',
                'title',
                'work_status',
                'verification_status',
                'contributors',
                'files',
            ],
        ]);
});
