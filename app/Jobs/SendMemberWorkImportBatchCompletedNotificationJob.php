<?php

namespace App\Jobs;

use App\Models\ImportBatch;
use App\Models\User;
use App\Notifications\System\MemberWorkImportBatchCompletedSystemNotification;
use App\Services\Notifications\SystemNotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendMemberWorkImportBatchCompletedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $importBatchId) {}

    public function handle(SystemNotificationService $systemNotifications): void
    {
        $batch = ImportBatch::query()->with('member.user')->find($this->importBatchId);
        $user = $batch?->member?->user;

        if (! $batch || ! $user) {
            return;
        }

        $failedRows = (int) ($batch->summary_json['submit_failed_rows'] ?? 0);

        $systemNotifications->send(
            $user,
            new MemberWorkImportBatchCompletedSystemNotification(
                (int) $batch->id,
                (int) $batch->submitted_rows,
                $failedRows,
            ),
            'member_work_import_batch_completed',
            'Bulk work import submit completed'
        );
    }
}
