<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'external_id',
        'uploaded_by_user_id',
        'category',
        'title',
        'document_type',
        'visibility',
        'description',
        'storage_disk',
        'file_path',
        'checksum',
        'last_accessed_at',
        'archived_at',
        'metadata_json',
    ];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'archived_at' => 'datetime',
        'metadata_json' => 'array',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('file')->singleFile();
    }
}
