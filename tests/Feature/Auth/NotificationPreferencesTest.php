<?php

use App\Models\NotificationPreference;
use App\Support\Notifications\NotificationChannels;

beforeEach(function () {
    ensureRole('member');
});

it('lists the preference taxonomy keys for both email and system channels', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $response = $this->getJson('/api/v1/me/notification-preferences')
        ->assertOk();

    $items = collect($response->json('data'));
    expect($items)->toHaveCount(16);

    $keys = $items->pluck('notification_key')->unique()->sort()->values()->all();
    expect($keys)->toEqualCanonicalizing([
        'account_security',
        'application_updates',
        'work_reviews',
        'licensing_updates',
        'payment_updates',
        'approval_updates',
        'general_announcements',
        'document_updates',
    ]);

    expect($items->pluck('channel')->unique()->sort()->values()->all())
        ->toEqualCanonicalizing([NotificationChannels::EMAIL, NotificationChannels::SYSTEM]);
});

it('normalizes concrete event keys to taxonomy keys when saving preferences', function () {
    $user = actingAsApiUser('member', ['account_type' => 'member']);

    $this->putJson('/api/v1/me/notification-preferences', [
        'preferences' => [
            [
                'channel' => NotificationChannels::EMAIL,
                'event_key' => 'payment_initiated',
                'enabled' => false,
            ],
            [
                'channel' => NotificationChannels::SYSTEM,
                'notification_key' => 'invoice_due_reminder',
                'is_enabled' => false,
            ],
        ],
    ])->assertOk();

    expect(NotificationPreference::query()->where('user_id', $user->id)->count())->toBe(2)
        ->and(NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('channel', NotificationChannels::EMAIL)
            ->where('notification_key', 'payment_updates')
            ->value('is_enabled'))->toBeFalse()
        ->and(NotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('channel', NotificationChannels::SYSTEM)
            ->where('notification_key', 'licensing_updates')
            ->value('is_enabled'))->toBeFalse();
});
