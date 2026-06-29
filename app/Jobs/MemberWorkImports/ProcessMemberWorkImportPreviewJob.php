<?php

namespace App\Jobs\MemberWorkImports;

use App\Models\ImportBatch;
use App\Support\MemberWorkImports\MemberWorkImportCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class ProcessMemberWorkImportPreviewJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $importBatchId,
        public string $path,
    ) {}

    public function handle(): void
    {
        $batch = ImportBatch::query()->find($this->importBatchId);

        if (! $batch || ! Storage::exists($this->path)) {
            return;
        }

        $batch->update(['status' => 'validating']);

        $parsed = MemberWorkImportCsv::parse((string) Storage::get($this->path));
        $header = $parsed['header'];
        $rows = $parsed['rows'];

        $missingColumns = array_diff(MemberWorkImportCsv::requiredColumns(), $header);
        if ($missingColumns !== []) {
            $batch->update([
                'status' => 'failed',
                'summary_json' => array_merge($batch->summary_json ?? [], [
                    'error' => 'Missing required CSV columns: '.implode(', ', $missingColumns),
                ]),
            ]);

            return;
        }

        $maxRows = (int) config('member_work_imports.max_rows_per_batch', 5000);
        if (count($rows) > $maxRows) {
            $batch->update([
                'status' => 'failed',
                'summary_json' => array_merge($batch->summary_json ?? [], [
                    'error' => "CSV exceeds the maximum of {$maxRows} rows per batch.",
                ]),
            ]);

            return;
        }

        $batch->failures()->delete();

        $valid = 0;
        $invalid = 0;
        $failures = [];
        $seenIdentifiers = [];

        foreach ($rows as $index => $payload) {
            $rowNumber = $index + 2;
            $errors = MemberWorkImportCsv::validateRow($payload);

            if (! MemberWorkImportCsv::isKnownLanguage($payload['primary_language'] ?? null)) {
                $errors['primary_language'][] = 'Primary language must match an active language from the platform list.';
            }

            if (($payload['type_of_work'] ?? null) === 'other_work_type' && blank($payload['other_work_type'] ?? null)) {
                $errors['other_work_type'][] = 'other_work_type is required when type_of_work is other_work_type.';
            }

            if (($payload['target_market'] ?? null) === 'other' && blank($payload['target_market_other'] ?? null)) {
                $errors['target_market_other'][] = 'target_market_other is required when target_market is other.';
            }

            $identifierKey = strtolower(trim((string) ($payload['identifier_type'] ?? '')))
                .':'.strtolower(trim((string) ($payload['identifier_value'] ?? '')));

            if ($identifierKey !== ':' && isset($seenIdentifiers[$identifierKey])) {
                $errors['identifier_value'][] = 'Duplicate identifier within this CSV batch.';
            } elseif ($identifierKey !== ':') {
                $seenIdentifiers[$identifierKey] = $rowNumber;
            }

            if ($errors === []) {
                $duplicates = app(\App\Actions\Works\FindDuplicateWorksAction::class)
                    ->execute(MemberWorkImportCsv::normalizeWorkPayload($payload));

                if ($duplicates->isNotEmpty()) {
                    $type = strtolower((string) ($payload['identifier_type'] ?? 'identifier'));
                    $errors['identifier_value'][] = "A work with this {$type} identifier already exists.";
                }
            }

            if ($errors !== []) {
                $invalid++;
                $failure = $batch->failures()->create([
                    'row_number' => $rowNumber,
                    'row_payload_json' => $payload,
                    'errors_json' => $errors,
                ]);
                $failures[] = [$failure->row_number, json_encode($payload), json_encode($errors)];

                continue;
            }

            $valid++;
        }

        $status = $invalid > 0
            ? ($valid > 0 ? 'validated_with_errors' : 'validated_with_errors')
            : 'validated';

        $batch->update([
            'status' => $status,
            'total_rows' => count($rows),
            'valid_rows' => $valid,
            'invalid_rows' => $invalid,
            'validated_at' => now(),
            'summary_json' => array_merge($batch->summary_json ?? [], [
                'source_path' => $this->path,
                'mode' => 'preview',
            ]),
        ]);

        $this->writeErrorReport($batch, $failures);
    }

    /** @param  list<array{0: int, 1: string, 2: string}>  $failures */
    protected function writeErrorReport(ImportBatch $batch, array $failures): void
    {
        if ($failures === []) {
            $batch->update(['error_report_path' => null]);

            return;
        }

        $reportPath = 'imports/reports/member_work_'.$batch->id.'_preview_errors.csv';
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
}
