<?php

namespace App\Actions\MemberOnboarding;

use App\Actions\Audit\LogAuditAction;
use App\Models\MemberApplication;
use App\Models\MemberApplicationDocument;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadMemberApplicationDocumentAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        MemberApplication $memberApplication,
        UploadedFile $file,
        string $documentType,
        User $uploadedBy,
        ?string $disk = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): MemberApplicationDocument {
        $disk ??= (string) config('filesystems.default', 'local');
        $existing = $memberApplication->documents()
            ->where('document_type', $documentType)
            ->latest('id')
            ->first();

        if ($existing?->file_path) {
            Storage::disk($disk)->delete($existing->file_path);
        }

        $path = $file->store('documents', $disk);

        $document = $existing ?: new MemberApplicationDocument([
            'member_application_id' => $memberApplication->id,
            'document_type' => $documentType,
        ]);

        $before = $existing?->toArray();

        $document->fill([
            'member_application_id' => $memberApplication->id,
            'document_type' => $documentType,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'verification_status' => 'pending',
            'uploaded_by_user_id' => $uploadedBy->id,
        ])->save();

        $fresh = $document->fresh();

        $this->logAuditAction->execute(
            $uploadedBy,
            $existing ? 'member_application_document_replaced' : 'member_application_document_uploaded',
            $fresh,
            $before,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}
