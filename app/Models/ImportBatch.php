<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by_user_id', 'member_id', 'import_type', 'status', 'total_rows', 'valid_rows', 'invalid_rows',
        'processed_rows', 'ready_rows', 'submitted_rows', 'source_filename', 'error_report_path', 'summary_json',
        'agreement_accepted', 'date_of_consent', 'validated_at', 'processed_at',
    ];

    protected $casts = [
        'summary_json' => 'array',
        'agreement_accepted' => 'boolean',
        'date_of_consent' => 'date',
        'validated_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function failures(): HasMany
    {
        return $this->hasMany(ImportRowFailure::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function memberWorkImportItems(): HasMany
    {
        return $this->hasMany(MemberWorkImportItem::class, 'import_batch_id');
    }

    public function isMemberWorkImport(): bool
    {
        return $this->import_type === 'member_works';
    }
}
