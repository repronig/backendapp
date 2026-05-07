<?php

namespace App\Support\Media;

use App\Models\Association;
use App\Models\Document;
use App\Models\Institution;
use App\Models\User;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\Support\PathGenerator\PathGenerator;

class UserAvatarPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        return $this->basePath($media);
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->basePath($media);
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->basePath($media);
    }

    private function basePath(Media $media): string
    {
        return match (true) {
            $media->model instanceof User => 'avatars/',
            $media->model instanceof Institution, $media->model instanceof Association => 'logos/',
            $media->model instanceof Document && $this->isDeclarationDocument($media->model) => 'declarations/',
            $media->model instanceof Document => 'documents/',
            default => 'documents/',
        };
    }

    private function isDeclarationDocument(Document $document): bool
    {
        $values = array_filter([
            $document->category,
            $document->document_type,
            $document->documentable_type,
        ]);

        return collect($values)->contains(
            fn (string $value): bool => str_contains(strtolower($value), 'declaration')
        );
    }
}
