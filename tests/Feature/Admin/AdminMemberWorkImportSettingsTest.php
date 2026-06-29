<?php

use App\Models\Setting;

beforeEach(function () {
    ensureRole('admin');
    config(['member_work_imports.enabled' => true]);
});

it('returns member work bulk import settings for admins', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->getJson('/api/v1/admin/works/bulk-import-settings')
        ->assertOk()
        ->assertJsonPath('data.member_work_bulk_import_enabled', true)
        ->assertJsonPath('data.env_enabled', true)
        ->assertJsonPath('data.effective_enabled', true);
});

it('allows admins to disable member work bulk import from the repertoire settings', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->putJson('/api/v1/admin/works/bulk-import-settings', [
        'member_work_bulk_import_enabled' => false,
    ])->assertOk()
        ->assertJsonPath('data.member_work_bulk_import_enabled', false)
        ->assertJsonPath('data.effective_enabled', false);

    $this->getJson('/api/v1/platform-settings')
        ->assertOk()
        ->assertJsonPath('data.features.member_work_bulk_import_enabled', false);

    $membership = Setting::query()
        ->where('group', Setting::GROUP_GENERAL)
        ->where('key', 'membership')
        ->first();

    expect($membership)->not->toBeNull()
        ->and($membership->value['member_work_bulk_import_enabled'] ?? null)->toBeFalse();
});

it('blocks admin updates when member work bulk import is disabled by env', function () {
    config(['member_work_imports.enabled' => false]);
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->putJson('/api/v1/admin/works/bulk-import-settings', [
        'member_work_bulk_import_enabled' => true,
    ])->assertStatus(422);
});

it('blocks members from bulk import when disabled from admin settings', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $this->putJson('/api/v1/admin/works/bulk-import-settings', [
        'member_work_bulk_import_enabled' => false,
    ])->assertOk();

    [$user] = actingAsApprovedMember();

    $this->get('/api/v1/work-import-batches/template')->assertNotFound();
});

it('allows admins to set the bulk upload tutorial video url', function () {
    actingAsApiUser('admin', ['account_type' => 'admin']);

    $url = 'https://www.youtube.com/watch?v=dQw4w9WgXcQ';

    $this->putJson('/api/v1/admin/works/bulk-import-settings', [
        'member_work_bulk_import_tutorial_video_url' => $url,
    ])->assertOk()
        ->assertJsonPath('data.member_work_bulk_import_tutorial_video_url', $url);

    $this->getJson('/api/v1/platform-settings')
        ->assertOk()
        ->assertJsonPath('data.features.member_work_bulk_import_tutorial_video_url', $url);
});
