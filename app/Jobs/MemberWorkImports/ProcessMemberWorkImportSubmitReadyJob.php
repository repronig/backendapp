<?php

namespace App\Jobs\MemberWorkImports;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Works\SubmitWorkAction;
use App\Jobs\SendMemberWorkImportBatchCompletedNotificationJob;
use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessMemberWorkImportSubmitReadyJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $importBatchId,
    ) {}

    public function handle(
        SubmitWorkAction $submitWorkAction,
        LogAuditAction $logAuditAction,
    ): void {
        $batch = ImportBatch::query()->with(['memberWorkImportItems.work'])->find($this->importBatchId);

        if (! $batch) {
            return;
        }

        $batch->update(['status' => 'submitting']);

        $actor = User::query()->find($batch->created_by_user_id);
        $submitted = 0;
        $failed = 0;
        $failures = [];

        $readyItems = $batch->memberWorkImportItems()->where('status', 'ready')->with('work')->get();

        foreach ($readyItems as $item) {
            if (! $item->work) {
                continue;
            }

            try {
                DB::transaction(function () use ($submitWorkAction, $item, $actor): void {
                    $submitWorkAction->execute(
                        $item->work,
                        $actor,
                        '127.0.0.1',
                        'ProcessMemberWorkImportSubmitReadyJob'
                    );

                    $item->update([
                        'status' => 'submitted',
                        'submit_errors_json' => null,
                    ]);
                });

                $submitted++;
            } catch (ValidationException $exception) {
                $failed++;
                $errors = $exception->errors();
                $item->update([
                    'status' => 'failed',
                    'submit_errors_json' => $errors,
                ]);
                $failures[] = [$item->row_number, (string) ($item->row_payload_json['title'] ?? ''), json_encode($errors)];
            } catch (Throwable $throwable) {
                $failed++;
                $item->update([
                    'status' => 'failed',
                    'submit_errors_json' => ['row' => [$throwable->getMessage()]],
                ]);
                $failures[] = [$item->row_number, (string) ($item->row_payload_json['title'] ?? ''), json_encode(['row' => [$throwable->getMessage()]])];
            }
        }

        $status = $failed > 0
            ? ($submitted > 0 ? 'submitted_with_errors' : 'submitted_with_errors')
            : 'submitted';

        $batch->update([
            'status' => $status,
            'submitted_rows' => $submitted,
            'summary_json' => array_merge($batch->summary_json ?? [], [
                'submit_failed_rows' => $failed,
            ]),
        ]);

        if ($failures !== []) {
            $reportPath = 'imports/reports/member_work_'.$batch->id.'_submit_errors.csv';
            $contents = "row_number,title,errors\n";
            foreach ($failures as $failure) {
                $contents .= implode(',', array_map(
                    fn ($value) => '"'.str_replace('"', '""', (string) $value).'"',
                    $failure
                ))."\n";
            }

            Storage::put($reportPath, $contents);
            $batch->update(['error_report_path' => $reportPath]);
        }

        $logAuditAction->execute(
            $actor,
            'member_work_batch_submit',
            $batch,
            null,
            ['submitted_rows' => $submitted, 'failed_rows' => $failed],
            '127.0.0.1',
            'ProcessMemberWorkImportSubmitReadyJob'
        );

        SendMemberWorkImportBatchCompletedNotificationJob::dispatch($batch->id)->afterCommit();
    }
}
