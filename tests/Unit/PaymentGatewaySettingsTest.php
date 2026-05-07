<?php

use App\Models\Setting;
use App\Support\Payments\PaymentGatewaySettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::query()->where('key', 'licensing')->delete();
});

it('exposes only enabled online gateways', function () {
    Setting::query()->create([
        'group' => 'general',
        'key' => 'licensing',
        'value' => [
            'paystack_enabled' => false,
            'flutterwave_enabled' => true,
            'default_online_gateway' => 'flutterwave',
        ],
    ]);

    $svc = app(PaymentGatewaySettings::class);

    expect($svc->enabledOnlineGateways())->toBe(['flutterwave'])
        ->and($svc->defaultOnlineGateway())->toBe('flutterwave');
});

it('throws when asserting a disabled gateway', function () {
    Setting::query()->create([
        'group' => 'general',
        'key' => 'licensing',
        'value' => [
            'paystack_enabled' => false,
            'flutterwave_enabled' => true,
        ],
    ]);

    $svc = app(PaymentGatewaySettings::class);

    expect(fn () => $svc->assertOnlineGatewayEnabled('paystack'))
        ->toThrow(ValidationException::class);
});

it('returns null default when no online gateways are enabled', function () {
    Setting::query()->create([
        'group' => 'general',
        'key' => 'licensing',
        'value' => [
            'paystack_enabled' => false,
            'flutterwave_enabled' => false,
            'default_online_gateway' => 'paystack',
            'offline_payment_enabled' => true,
        ],
    ]);

    $svc = app(PaymentGatewaySettings::class);

    expect($svc->enabledOnlineGateways())->toBe([])
        ->and($svc->defaultOnlineGateway())->toBeNull();
});

it('maps legacy payment_gateway to toggles when explicit flags are absent', function () {
    Setting::query()->create([
        'group' => 'general',
        'key' => 'licensing',
        'value' => [
            'payment_gateway' => 'flutterwave',
        ],
    ]);

    expect(app(PaymentGatewaySettings::class)->enabledOnlineGateways())->toBe(['flutterwave']);
});
