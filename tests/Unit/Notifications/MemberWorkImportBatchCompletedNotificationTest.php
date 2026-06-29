<?php

use App\Notifications\System\MemberWorkImportBatchCompletedSystemNotification;

it('builds member work import batch completed notification payload', function () {
    $notifiable = new stdClass;

    $success = new MemberWorkImportBatchCompletedSystemNotification(42, 3, 0);
    $withFailures = new MemberWorkImportBatchCompletedSystemNotification(42, 2, 1);

    $successPayload = $success->toArray($notifiable);
    $failurePayload = $withFailures->toArray($notifiable);

    expect($successPayload['action_url'])->toBe('/member/works/bulk/42')
        ->and($successPayload['type'])->toBe('member_work_import_batch_completed')
        ->and($successPayload['severity'])->toBe('success')
        ->and($successPayload['meta']['submitted_rows'])->toBe(3)
        ->and($successPayload['meta']['failed_rows'])->toBe(0)
        ->and($failurePayload['severity'])->toBe('warning')
        ->and($failurePayload['meta']['submitted_rows'])->toBe(2)
        ->and($failurePayload['meta']['failed_rows'])->toBe(1);
});
