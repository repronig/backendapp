<?php

namespace App\Actions\Documents;

use App\Actions\Audit\LogAuditAction;
use App\Models\Document;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteDocumentAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(
        Document $document,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): void {
        DB::transaction(function () use ($document, $actor, $ipAddress, $userAgent): void {
            $before = $document->toArray();

            if (filled($document->file_path)) {
                $defaultDisk = (string) config('filesystems.default', 'local');
                Storage::disk($document->storage_disk ?: $defaultDisk)->delete($document->file_path);
            }

            $document->clearMediaCollection('file');
            $document->delete();

            $this->logAuditAction->execute(
                $actor,
                'document_deleted',
                $document,
                $before,
                null,
                $ipAddress,
                $userAgent,
            );
        });
    }
}
