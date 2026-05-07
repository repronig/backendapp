<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Models\Work;
use Illuminate\Support\Facades\Storage;

class DeleteWorkAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        Work $work,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        $before = $work->toArray();

        $work->loadMissing('files');
        foreach ($work->files as $file) {
            if ($file->file_path) {
                Storage::disk('public')->delete($file->file_path);
            }
        }

        $work->delete();

        $this->logAuditAction->execute(
            $actor,
            'work_deleted',
            $work,
            $before,
            null,
            $ipAddress,
            $userAgent
        );
    }
}
