<?php

use App\Actions\Access\BuildCurrentUserContextAction;
use App\Models\Association;
use App\Models\Member;
use App\Models\User;

beforeEach(function () {
    ensureRole('member');
});

it('reports member_approved from approval_status not merely member row presence', function () {
    $user = User::factory()->create(['account_type' => 'member']);
    $user->assignRole('member');

    Member::factory()->create([
        'user_id' => $user->id,
        'association_id' => Association::factory(),
        'approval_status' => 'pending',
        'account_status' => 'pending',
    ]);

    $pending = app(BuildCurrentUserContextAction::class)->execute(User::query()->findOrFail($user->id));

    expect($pending['onboarding_status']['member_approved'])->toBeFalse();

    Member::query()->where('user_id', $user->id)->update([
        'approval_status' => 'approved',
        'account_status' => 'active',
    ]);

    $approved = app(BuildCurrentUserContextAction::class)->execute(User::query()->findOrFail($user->id));

    expect($approved['onboarding_status']['member_approved'])->toBeTrue();
});
