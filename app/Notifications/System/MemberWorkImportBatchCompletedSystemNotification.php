<?php

namespace App\Notifications\System;

class MemberWorkImportBatchCompletedSystemNotification extends BaseSystemNotification
{
    public function __construct(
        protected int $batchId,
        protected int $submittedRows,
        protected int $failedRows,
    ) {}

    public function toArray(object $notifiable): array
    {
        $hasFailures = $this->failedRows > 0;
        $title = $hasFailures ? 'Bulk work submit completed with errors' : 'Bulk work submit completed';
        $message = $hasFailures
            ? sprintf('%d work(s) submitted successfully and %d failed. Review your import batch for details.', $this->submittedRows, $this->failedRows)
            : sprintf('%d work(s) from your bulk import were submitted successfully.', $this->submittedRows);

        return [
            ...$this->basePayload(
                'member_work_import_batch_completed',
                $title,
                $message,
                $hasFailures ? 'warning' : 'success',
                '/member/works/bulk/'.$this->batchId,
                [
                    'entity_type' => 'import_batch',
                    'entity_id' => $this->batchId,
                    'submitted_rows' => $this->submittedRows,
                    'failed_rows' => $this->failedRows,
                ]
            ),
            'category' => 'repertoire',
        ];
    }
}
