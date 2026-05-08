<?php

namespace App\Actions\Institutions;

use App\Actions\Audit\LogAuditAction;
use App\Models\Institution;
use App\Models\InstitutionDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;

class UploadInstitutionDocumentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Institution $institution,
        UploadedFile $file,
        string $documentType,
        User $uploadedBy,
        ?string $disk = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): InstitutionDocument {
        $disk ??= (string) config('filesystems.default', 'local');
        $path = $file->store('documents', $disk);

        $document = InstitutionDocument::create([
            'institution_id' => $institution->id,
            'document_type' => $documentType,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'verification_status' => 'pending',
            'uploaded_by_user_id' => $uploadedBy->id,
        ]);

        $fresh = $document->fresh();

        $this->logAuditAction->execute(
            $uploadedBy,
            'institution_document_uploaded',
            $fresh,
            null,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}