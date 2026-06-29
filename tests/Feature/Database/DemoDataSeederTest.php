<?php

use App\Models\Institution;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\AccessControlSeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\DemoUsersSeeder;
use Database\Seeders\LanguageSeeder;
use Database\Seeders\ReferenceDataSeeder;
use Illuminate\Support\Facades\Hash;

it('seeds canonical demo member and institution portal users', function () {
    $this->seed([
        AccessControlSeeder::class,
        ReferenceDataSeeder::class,
        LanguageSeeder::class,
        DemoUsersSeeder::class,
        DemoDataSeeder::class,
    ]);

    $memberUser = User::query()->where('email', DemoDataSeeder::DEMO_MEMBER_EMAIL)->first();
    expect($memberUser)->not->toBeNull()
        ->and(Hash::check(DemoDataSeeder::DEMO_MEMBER_PASSWORD, $memberUser->password))->toBeTrue();

    $member = Member::query()->where('user_id', $memberUser->id)->first();
    expect($member)->not->toBeNull()
        ->and($member->approval_status)->toBe('approved')
        ->and($member->account_status)->toBe('active');

    $institutionUser = User::query()->where('email', DemoDataSeeder::DEMO_INSTITUTION_USER_EMAIL)->first();
    expect($institutionUser)->not->toBeNull()
        ->and(Hash::check(DemoDataSeeder::DEMO_INSTITUTION_PASSWORD, $institutionUser->password))->toBeTrue();

    $institution = Institution::query()->where('email', 'registry@seededuniversity.edu.ng')->first();
    expect($institution)->not->toBeNull()
        ->and($institution->onboarding_status)->toBe('approved')
        ->and($institution->institutionUsers()->where('user_id', $institutionUser->id)->exists())->toBeTrue();
});
