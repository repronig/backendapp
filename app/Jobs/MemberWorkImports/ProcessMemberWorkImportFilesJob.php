<?php

namespace App\Jobs\MemberWorkImports;

use App\Actions\Audit\LogAuditAction;
use App\Actions\Works\EvaluateWorkReadinessAction;
use App\Actions\Works\UploadWorkFileAction;
use App\Models\ImportBatch;
use App\Models\MemberWorkImportItem;
use App\Models\User;
use App\Support\MemberWorkImports\MemberWorkImportCsv;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Throwable;
use ZipArchive;

class ProcessMemberWorkImportFilesJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $importBatchId,
        public string $zipPath,
    ) {}

    public function handle(
        UploadWorkFileAction $uploadWorkFileAction,
        EvaluateWorkReadinessAction $evaluateWorkReadinessAction,
        LogAuditAction $logAuditAction,
    ): void {
        $batch = ImportBatch::query()->with(['memberWorkImportItems.work.files', 'member.user'])->find($this->importBatchId);

        if (! $batch || ! Storage::exists($this->zipPath)) {
            return;
        }

        $batch->update(['status' => 'processing_files']);

        $actor = User::query()->find($batch->created_by_user_id);
        $absoluteZipPath = Storage::path($this->zipPath);
        $extractDir = storage_path('app/imports/extracted/member_work_'.$batch->id.'_'.uniqid());

        if (! is_dir($extractDir) && ! mkdir($extractDir, 0755, true) && ! is_dir($extractDir)) {
            $batch->update([
                'status' => 'processed_with_errors',
                'summary_json' => array_merge($batch->summary_json ?? [], [
                    'zip_error' => 'Could not create extraction directory.',
                ]),
            ]);

            return;
        }

        $zip = new ZipArchive;
        if ($zip->open($absoluteZipPath) !== true) {
            $batch->update([
                'status' => 'processed_with_errors',
                'summary_json' => array_merge($batch->summary_json ?? [], [
                    'zip_error' => 'Could not open ZIP archive.',
                ]),
            ]);

            return;
        }

        $zip->extractTo($extractDir);
        $zip->close();

        /** @var array<int, array{attached: list<array{filename: string, file_type: string}>, failed: list<array{filename: string, error: string}>}> $itemFileResults */
        $itemFileResults = [];
        $attached = 0;
        $fileFailures = [];

        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($extractDir));
        foreach ($iterator as $fileInfo) {
            if (! $fileInfo->isFile()) {
                continue;
            }

            $basename = $fileInfo->getBasename();
            $mapping = $this->resolveFileMapping($basename);

            if ($mapping === null) {
                continue;
            }

            [$sanitizedIdentifier, $fileType] = $mapping;
            $item = MemberWorkImportCsv::findItemForZipFilenamePrefix($batch->memberWorkImportItems, $sanitizedIdentifier);

            if (! $item || ! $item->work) {
                $fileFailures[] = [$basename, 'No matching draft work found for identifier.'];
                continue;
            }

            $itemFileResults[$item->id] ??= ['attached' => [], 'failed' => []];

            try {
                $uploadedFile = new UploadedFile(
                    $fileInfo->getPathname(),
                    $basename,
                    mime_content_type($fileInfo->getPathname()) ?: null,
                    null,
                    true
                );

                $this->assertAllowedUpload($uploadedFile, $fileType);

                $uploadWorkFileAction->execute(
                    $item->work,
                    $uploadedFile,
                    $fileType,
                    $actor ?? $batch->member->user,
                    null,
                    '127.0.0.1',
                    'ProcessMemberWorkImportFilesJob'
                );

                $itemFileResults[$item->id]['attached'][] = [
                    'filename' => $basename,
                    'file_type' => $fileType,
                ];
                $attached++;
            } catch (Throwable $throwable) {
                $itemFileResults[$item->id]['failed'][] = [
                    'filename' => $basename,
                    'error' => $throwable->getMessage(),
                ];
                $fileFailures[] = [$basename, $throwable->getMessage()];
            }
        }

        foreach ($batch->memberWorkImportItems()->with('work.files')->get() as $item) {
            $this->persistItemFileResults($item, $itemFileResults[$item->id] ?? null);

            if (! $item->work) {
                continue;
            }

            $readiness = $evaluateWorkReadinessAction->execute($item->work);
            $item->update([
                'status' => $readiness['ready'] ? 'ready' : 'draft',
                'readiness_errors_json' => $readiness['ready'] ? null : $readiness['errors'],
            ]);
        }

        $readyCount = $batch->memberWorkImportItems()->where('status', 'ready')->count();
        $batch->update([
            'status' => $fileFailures !== [] ? 'processed_with_errors' : 'processed',
            'ready_rows' => $readyCount,
            'summary_json' => array_merge($batch->summary_json ?? [], [
                'zip_path' => $this->zipPath,
                'files_attached' => $attached,
                'file_failures' => $fileFailures,
            ]),
        ]);

        $this->deleteDirectory($extractDir);

        $logAuditAction->execute(
            $actor,
            'member_work_import_files_processed',
            $batch,
            null,
            ['files_attached' => $attached, 'file_failures' => count($fileFailures)],
            '127.0.0.1',
            'ProcessMemberWorkImportFilesJob'
        );
    }

    /** @param  array{attached: list<array{filename: string, file_type: string}>, failed: list<array{filename: string, error: string}>}|null  $results */
    protected function persistItemFileResults(MemberWorkImportItem $item, ?array $results): void
    {
        if ($results === null) {
            return;
        }

        $existing = is_array($item->file_results_json) ? $item->file_results_json : ['attached' => [], 'failed' => []];
        $merged = [
            'attached' => array_values(array_merge($existing['attached'] ?? [], $results['attached'])),
            'failed' => array_values(array_merge($existing['failed'] ?? [], $results['failed'])),
        ];

        $item->update(['file_results_json' => $merged]);
    }

    /** @return array{0: string, 1: string}|null */
    protected function resolveFileMapping(string $basename): ?array
    {
        if (preg_match('/^(.+)_cover\.(jpe?g|png|webp)$/i', $basename, $matches) === 1) {
            return [$matches[1], 'cover_image'];
        }

        if (preg_match('/^(.+)_copyright\.pdf$/i', $basename, $matches) === 1) {
            return [$matches[1], 'copyright_page'];
        }

        if (preg_match('/^(.+)_proof\.pdf$/i', $basename, $matches) === 1) {
            return [$matches[1], 'proof_of_ownership'];
        }

        return null;
    }

    protected function assertAllowedUpload(UploadedFile $file, string $fileType): void
    {
        $allowedMimes = match ($fileType) {
            'cover_image' => ['image/jpeg', 'image/png', 'image/webp'],
            default => ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'],
        };

        $mime = $file->getMimeType();
        if (! in_array($mime, $allowedMimes, true)) {
            throw new \RuntimeException("File type {$mime} is not allowed for {$fileType}.");
        }

        if ($file->getSize() > 10240 * 1024) {
            throw new \RuntimeException('File exceeds the 10 MB limit.');
        }
    }

    protected function deleteDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir()) {
                rmdir($fileInfo->getPathname());
            } else {
                unlink($fileInfo->getPathname());
            }
        }

        rmdir($directory);
    }
}
