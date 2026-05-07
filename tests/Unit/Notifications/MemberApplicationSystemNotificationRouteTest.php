<?php

use App\Notifications\System\MemberApplicationApprovedSystemNotification;
use App\Notifications\System\MemberApplicationChangesRequestedSystemNotification;
use App\Notifications\System\MemberApplicationRejectedSystemNotification;

it('uses member onboarding route for member application system notifications', function () {
    $notifiable = new stdClass;

    $approved = new MemberApplicationApprovedSystemNotification('MEM-1001', 'app-ext-1');
    $rejected = new MemberApplicationRejectedSystemNotification('Incomplete identity document', 'app-ext-2');
    $changesRequested = new MemberApplicationChangesRequestedSystemNotification('Please upload a clearer file.', 'app-ext-3');

    expect($approved->toArray($notifiable)['action_url'])->toBe('/member/onboarding')
        ->and($rejected->toArray($notifiable)['action_url'])->toBe('/member/onboarding')
        ->and($changesRequested->toArray($notifiable)['action_url'])->toBe('/member/onboarding');
});
