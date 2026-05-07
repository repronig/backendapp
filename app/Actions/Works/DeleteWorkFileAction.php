<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Models\WorkFile;
use Illuminate\Support\Facades\Storage;

class DeleteWorkFileAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        WorkFile $file,
        string $disk = 'public',
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $before = $file->toArray();

        Storage::disk($disk)->delete($file->file_path);
        $file->delete();

        $this->logAuditAction->execute(
            $actor,
            'work_file_deleted',
            $file,
            $before,
            null,
            $ipAddress,
            $userAgent
        );
    }
}