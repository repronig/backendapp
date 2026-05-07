<?php

namespace App\Actions\Works;

use App\Actions\Audit\LogAuditAction;
use App\Models\User;
use App\Models\Work;
use App\Models\WorkFile;
use Illuminate\Http\UploadedFile;

class UploadWorkFileAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Work $work,
        UploadedFile $file,
        string $fileType,
        User $uploadedBy,
        string $disk = 'public',
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): WorkFile {
        $path = $file->store('works', $disk);

        $workFile = WorkFile::create([
            'work_id' => $work->id,
            'file_type' => $fileType,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_by_user_id' => $uploadedBy->id,
        ]);

        $fresh = $workFile->fresh();

        $this->logAuditAction->execute(
            $uploadedBy,
            'work_file_uploaded',
            $fresh,
            null,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}