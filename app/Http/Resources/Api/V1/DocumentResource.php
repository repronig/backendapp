<?php

namespace App\Http\Resources\Api\V1;

use App\Support\PublicAssetUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metadata = is_array($this->metadata_json) ? $this->metadata_json : [];
        $media = $this->getFirstMedia('file');
        $fileUrl = PublicAssetUrl::publicStorageUrl($this->file_path, $request) ?: PublicAssetUrl::fromMedia($media, null, $request);

        return [
            'id' => $this->id,
            'external_id' => $this->external_id,
            'category' => $this->category,
            'title' => $this->title,
            'document_type' => $this->document_type,
            'visibility' => $this->visibility,
            'description' => $this->description,
            'checksum' => $this->checksum,
            'file_url' => $fileUrl,
            'download_url' => $fileUrl,
            'file_name' => $metadata['original_name'] ?? $media?->file_name,
            'mime_type' => $metadata['mime_type'] ?? $media?->mime_type,
            'file_size' => $metadata['size'] ?? $media?->size,
            'metadata' => $metadata,
            'uploaded_by' => $this->whenLoaded('uploadedBy', fn () => [
                'id' => $this->uploadedBy?->id,
                'name' => $this->uploadedBy?->name,
            ]),
            'uploaded_at' => optional($this->created_at)->toIso8601String(),
            'created_at' => optional($this->created_at)->toIso8601String(),
        ];
    }
}