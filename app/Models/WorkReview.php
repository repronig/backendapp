<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_id',
        'reviewer_user_id',
        'decision',
        'reason_code',
        'review_note',
        'evidence_requested',
        'reviewed_at',
        'metadata_json',
    ];

    protected $casts = [
        'evidence_requested' => 'boolean',
        'reviewed_at' => 'datetime',
        'metadata_json' => 'array',
    ];

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewer_user_id');
    }
}
