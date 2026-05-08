<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('stores avatar on MEDIA_DISK when media disk differs from default filesystem disk', function () {
    ensureRole('member');

    config()->set('filesystems.default', 'local');
    config()->set('media-library.disk_name', 's3');
    Storage::fake('s3');

    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $this->postJson('/api/v1/me/avatar', [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertOk();

    $user->refresh();

    expect($user->avatar_path)->not()->toBeNull();
    Storage::disk('s3')->assertExists($user->avatar_path);
});

