<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class WorkContributor extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'work_id',
        'member_id',
        'contributor_name',
        'contributor_role',
        'right_type',
        'ownership_percentage',
        'is_disputed',
        'dispute_reason_code',
        'dispute_reason',
        'disputed_by_user_id',
        'disputed_at',
        'territory_scope',
    ];

    protected $casts = [
        'ownership_percentage' => 'decimal:2',
        'is_disputed' => 'boolean',
        'disputed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $contributor): void {
            $contributor->external_id ??= (string) Str::uuid();
        });
    }

    public function work(): BelongsTo
    {
        return $this->belongsTo(Work::class);
    }

    public function disputedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disputed_by_user_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}