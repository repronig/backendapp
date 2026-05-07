<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('prefers the general group when two rows share the same key', function () {
    Setting::query()->create([
        'group' => 'platform',
        'key' => 'licensing',
        'value' => ['paystack_enabled' => true, 'flutterwave_enabled' => true],
    ]);
    Setting::query()->create([
        'group' => Setting::GROUP_GENERAL,
        'key' => 'licensing',
        'value' => ['paystack_enabled' => false, 'flutterwave_enabled' => true],
    ]);

    $licensing = Setting::collectionKeyedByUniqueSettingKey()->get('licensing');

    expect($licensing)->toBeArray()
        ->and($licensing['paystack_enabled'])->toBeFalse();
});
