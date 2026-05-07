<?php

namespace App\Models;

use App\Enums\MemberApplicationStatus;
use App\Support\References\ReferenceCodeGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MemberApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'external_id',
        'application_reference',
        'user_id',
        'association_id',
        'applicant_type',
        'member_author_type',
        'member_author_category',
        'application_status',
        'submission_stage',
        'nationality',
        'country_of_residence',
        'is_diaspora',
        'bank_name',
        'bank_account_number',
        'bank_account_owner_name',
        'next_of_kin_name',
        'next_of_kin_phone',
        'publisher_organisation_name',
        'publisher_tin',
        'publisher_location_address',
        'publisher_postal_address',
        'publisher_email',
        'publisher_phone',
        'consent_accepted',
        'consent_date',
        'notes',
        'member_provided_id',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected $casts = [
        'is_diaspora' => 'boolean',
        'consent_accepted' => 'boolean',
        'consent_date' => 'date',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $memberApplication): void {
            $memberApplication->external_id ??= (string) Str::uuid();

            if (empty($memberApplication->application_reference)) {
                $memberApplication->application_reference = app(ReferenceCodeGenerator::class)
                    ->generateMemberApplicationReference();
            }
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

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MemberApplicationDocument::class);
    }

    public function isEditableByApplicant(): bool
    {
        return in_array($this->application_status, [
            MemberApplicationStatus::Draft->value,
            MemberApplicationStatus::ChangesRequested->value,
        ], true);
    }

    public function isApproved(): bool
    {
        return $this->application_status === MemberApplicationStatus::Approved->value;
    }
}
