<?php

namespace App\Actions\Documents;

use App\Actions\Audit\LogAuditAction;
use App\Models\Document;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UploadDocumentAction
{
    public function __construct(protected LogAuditAction $logAuditAction)
    {
    }

    public function execute(
        Model $documentable,
        UploadedFile $file,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Document {
        return DB::transaction(function () use ($documentable, $file, $data, $actor, $ipAddress, $userAgent): Document {
            $disk = 'public';
            $category = strtolower((string) ($data['category'] ?? ''));
            $documentType = strtolower((string) ($data['document_type'] ?? ''));
            $folder = str_contains($category, 'declaration') || str_contains($documentType, 'declaration') ? 'declarations' : 'documents';
            $path = $file->store($folder, $disk);

            $document = $documentable->documents()->create([
                'external_id' => (string) Str::uuid(),
                'uploaded_by_user_id' => $actor?->id,
                'category' => $data['category'],
                'title' => $data['title'],
                'document_type' => $data['document_type'] ?? 'supporting_record',
                'visibility' => $data['visibility'] ?? 'private',
                'description' => $data['description'] ?? null,
                'storage_disk' => $disk,
                'file_path' => $path,
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'metadata_json' => [
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ],
            ]);

            $fresh = $document->fresh(['uploadedBy']);

            $this->logAuditAction->execute(
                $actor,
                'document_uploaded',
                $fresh,
                null,
                $fresh->toArray(),
                $ipAddress,
                $userAgent,
            );

            return $fresh;
        });
    }
}
