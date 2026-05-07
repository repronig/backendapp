<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'association_id',
        'member_code',
        'member_type',
        'member_provided_id',
        'external_id',
        'approval_status',
        'account_status',
        'status_reason_code',
        'status_reason',
        'status_changed_by_user_id',
        'status_changed_at',
        'joined_at',
        'activated_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'activated_at' => 'datetime',
        'status_changed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $member): void {
            $member->external_id ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function association(): BelongsTo
    {
        return $this->belongsTo(Association::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(MemberProfile::class);
    }

    public function statusChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'status_changed_by_user_id');
    }

    public function works(): HasMany
    {
        return $this->hasMany(Work::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
