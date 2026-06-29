<?php

namespace App\Jobs\MemberWorkImports;

use App\Actions\Audit\LogAuditAction;
use App\Actions\MemberWorkImports\ImportMemberWorkRowAction;
use App\Actions\Works\EvaluateWorkReadinessAction;
use App\Models\ImportBatch;
use App\Models\ImportRowFailure;
use App\Models\User;
use App\Support\MemberWorkImports\MemberWorkImportCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProcessMemberWorkImportDraftsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $importBatchId,
        public string $path,
    ) {}

    public function handle(
        ImportMemberWorkRowAction $importMemberWorkRowAction,
        EvaluateWorkReadinessAction $evaluateWorkReadinessAction,
        LogAuditAction $logAuditAction,
    ): void {
        $batch = ImportBatch::query()->with('member.user')->find($this->importBatchId);

        if (! $batch || ! $batch->member || ! Storage::exists($this->path)) {
            return;
        }

        $batch->update(['status' => 'processing']);

        $parsed = MemberWorkImportCsv::parse((string) Storage::get($this->path));
        $rows = $parsed['rows'];

        $failedRowNumbers = ImportRowFailure::query()
            ->where('import_batch_id', $batch->id)
            ->pluck('row_number')
            ->all();

        $failedLookup = array_fill_keys($failedRowNumbers, true);

        $processed = 0;
        $invalid = 0;
        $failures = [];
        $actor = User::query()->find($batch->created_by_user_id);

        foreach ($rows as $index => $payload) {
            $rowNumber = $index + 2;

            if (isset($failedLookup[$rowNumber])) {
                continue;
            }

            try {
                $item = null;
                DB::transaction(function () use (
                    $batch,
                    $payload,
                    $rowNumber,
                    $actor,
                    $importMemberWorkRowAction,
                    $evaluateWorkReadinessAction,
                    &$item,
                ): void {
                    $item = $importMemberWorkRowAction->execute(
                        $batch,
                        $batch->member,
                        MemberWorkImportCsv::normalizeWorkPayload($payload),
                        $rowNumber,
                        $actor,
                        '127.0.0.1',
                        'ProcessMemberWorkImportDraftsJob'
                    );

                    $item->load('work');
                    $readiness = $evaluateWorkReadinessAction->execute($item->work);
                    $item->update([
                        'status' => $readiness['ready'] ? 'ready' : 'draft',
                        'readiness_errors_json' => $readiness['ready'] ? null : $readiness['errors'],
                    ]);
                });

                $processed++;
            } catch (ValidationException $exception) {
                $invalid++;
                $errors = $exception->errors();
                $failure = $batch->failures()->create([
                    'row_number' => $rowNumber,
                    'row_payload_json' => $payload,
                    'errors_json' => $errors,
                ]);
                $failures[] = [$failure->row_number, json_encode($payload), json_encode($errors)];
            } catch (Throwable $throwable) {
                $invalid++;
                $failure = $batch->failures()->create([
                    'row_number' => $rowNumber,
                    'row_payload_json' => $payload,
                    'errors_json' => ['row' => [$throwable->getMessage()]],
                ]);
                $failures[] = [$failure->row_number, json_encode($payload), json_encode(['row' => [$throwable->getMessage()]])];
            }
        }

        $this->refreshBatchCounters($batch);

        $status = $invalid > 0 ? 'processed_with_errors' : 'processed';
        $batch->update([
            'status' => $status,
            'processed_rows' => $processed,
            'invalid_rows' => $batch->invalid_rows + $invalid,
            'processed_at' => now(),
            'summary_json' => array_merge($batch->summary_json ?? [], [
                'mode' => 'process',
            ]),
        ]);

        if ($failures !== []) {
            $this->appendProcessErrorReport($batch, $failures);
        }

        $logAuditAction->execute(
            $actor,
            'member_work_import_processed',
            $batch,
            null,
            ['processed_rows' => $processed, 'invalid_rows' => $invalid],
            '127.0.0.1',
            'ProcessMemberWorkImportDraftsJob'
        );
    }

    /** @param  list<array{0: int, 1: string, 2: string}>  $failures */
    protected function appendProcessErrorReport(ImportBatch $batch, array $failures): void
    {
        $reportPath = 'imports/reports/member_work_'.$batch->id.'_process_errors.csv';
        $contents = "row_number,row_payload,errors\n";
        foreach ($failures as $failure) {
            $contents .= implode(',', array_map(
                fn ($value) => '"'.str_replace('"', '""', (string) $value).'"',
                $failure
            ))."\n";
        }

        Storage::put($reportPath, $contents);
        $batch->update(['error_report_path' => $reportPath]);
    }

    protected function refreshBatchCounters(ImportBatch $batch): void
    {
        $counts = $batch->memberWorkImportItems()
            ->selectRaw("status, COUNT(*) as aggregate")
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $batch->update([
            'ready_rows' => (int) ($counts['ready'] ?? 0),
            'submitted_rows' => (int) ($counts['submitted'] ?? 0),
        ]);
    }
}
