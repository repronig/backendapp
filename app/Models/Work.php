<?php

namespace App\Models;

use App\Enums\WorkStatus;
use App\Enums\WorkVerificationStatus;
use App\Support\References\ReferenceCodeGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Work extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'type_of_work',
        'title',
        'subtitle',
        'publication_year',
        'synopsis',
        'primary_language',
        'work_format',
        'identifier_type',
        'reference_number',
        'external_id',
        'identifier_value',
        'duplicate_fingerprint',
        'doi',
        'publisher_name',
        'target_market',
        'target_market_other',
        'date_of_consent',
        'production_status',
        'agreement_accepted',
        'other_work_type',
        'notes',
        'work_status',
        'verification_status',
        'submitted_at',
        'verified_at',
        'verified_by_user_id',
        'last_reviewed_by_user_id',
        'last_reviewed_at',
        'review_reason',
        'is_disputed',
        'is_restricted',
        'update_request_status',
        'update_requested_at',
        'update_requested_by_member_id',
        'update_request_note',
        'update_request_reviewed_at',
        'update_request_reviewed_by_user_id',
        'update_request_review_note',
        'governance_reason_code',
        'governance_reason',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'last_reviewed_at' => 'datetime',
        'update_requested_at' => 'datetime',
        'update_request_reviewed_at' => 'datetime',
        'date_of_consent' => 'date',
        'agreement_accepted' => 'boolean',
        'is_disputed' => 'boolean',
        'is_restricted' => 'boolean',
        'work_status' => WorkStatus::class,
        'verification_status' => WorkVerificationStatus::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (self $work): void {
            $work->external_id ??= (string) Str::uuid();
            $work->reference_number ??= app(ReferenceCodeGenerator::class)->generateWorkReferenceNumber();
            $work->duplicate_fingerprint ??= static::makeDuplicateFingerprint($work->toArray());
        });

        static::saving(function (self $work): void {
            $work->duplicate_fingerprint = static::makeDuplicateFingerprint($work->toArray());
        });
    }

    public static function makeDuplicateFingerprint(array $attributes): ?string
    {
        $identifierType = strtolower((string) ($attributes['identifier_type'] ?? ''));
        $identifierValue = strtolower(trim((string) ($attributes['identifier_value'] ?? '')));
        $title = strtolower(trim((string) ($attributes['title'] ?? '')));
        $publisher = strtolower(trim((string) ($attributes['publisher_name'] ?? '')));

        if ($identifierValue !== '' && in_array($identifierType, ['isbn', 'issn', 'isni', 'iswc', 'url', 'other'], true)) {
            return hash('sha256', $identifierType.'|'.$identifierValue);
        }

        if ($title === '') {
            return null;
        }

        return hash('sha256', $title.'|'.$publisher.'|'.(string) ($attributes['publication_year'] ?? ''));
    }

    /**
     * When the member provides a non-empty identifier of these types, uniqueness is enforced
     * against other works (duplicate_fingerprint on type + normalized value).
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function shouldEnforceIdentifierUniqueness(array $attributes): bool
    {
        $identifierType = strtolower((string) ($attributes['identifier_type'] ?? ''));
        $identifierValue = trim((string) ($attributes['identifier_value'] ?? ''));

        if ($identifierValue === '') {
            return false;
        }

        return in_array($identifierType, ['isbn', 'issn', 'isni', 'iswc', 'url', 'other'], true);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function lastReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_reviewed_by_user_id');
    }

    public function contributors(): HasMany
    {
        return $this->hasMany(WorkContributor::class);
    }

    public function files(): HasMany
    {
        return $this->hasMany(WorkFile::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(WorkReview::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
