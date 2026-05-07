<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Support\PublicAssetUrl;

class InstitutionDocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $fileUrl = PublicAssetUrl::publicStorageUrl($this->file_path, $request);

        return [
            'id' => $this->id,
            'document_type' => $this->document_type,
            'file_name' => $this->file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'file_url' => $fileUrl,
            'download_url' => $fileUrl,
            'verification_status' => $this->verification_status,
            'verified_at' => optional($this->verified_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}