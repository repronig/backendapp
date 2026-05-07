<?php

namespace App\Models;

use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Institution extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'external_id',
        'name',
        'institution_type',
        'registration_number',
        'licence_id',
        'year_established',
        'email',
        'phone',
        'contact_person_name',
        'contact_person_title',
        'faculties_count',
        'member_count',
        'branches_count',
        'onboarding_status',
        'account_status',
        'logo_path',
        'governance_status',
        'governance_reason_code',
        'governance_reason',
        'governance_changed_by_user_id',
        'governance_changed_at',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'city_id',
        'state_id',
        'country',
        'postal_code',
        'approved_by_user_id',
        'approved_at',
        'licence_id_generated_at',
        'licensing_terms_accepted_at',
        'licensing_terms_acknowledged_on',
        'licensing_terms_version_accepted',
    ];

    protected $casts = [
        'year_established' => 'integer',
        'faculties_count' => 'integer',
        'member_count' => 'integer',
        'branches_count' => 'integer',
        'approved_at' => 'datetime',
        'licence_id_generated_at' => 'datetime',
        'governance_changed_at' => 'datetime',
        'licensing_terms_accepted_at' => 'datetime',
        'licensing_terms_acknowledged_on' => 'date',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $institution): void {
            $institution->external_id ??= (string) Str::uuid();
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }

    public function profile(): HasOne
    {
        return $this->hasOne(InstitutionProfile::class);
    }

    public function legacyDocuments(): HasMany
    {
        return $this->hasMany(InstitutionDocument::class);
    }

    public function licences(): HasMany
    {
        return $this->hasMany(Licence::class);
    }

    public function annualDeclarations(): HasMany
    {
        return $this->hasMany(InstitutionAnnualDeclaration::class);
    }

    public function latestAnnualDeclaration(): HasOne
    {
        return $this->hasOne(InstitutionAnnualDeclaration::class)->latestOfMany('licensing_year');
    }

    public function usageDeclarations(): HasMany
    {
        return $this->hasMany(UsageDeclaration::class);
    }

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function governanceChangedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'governance_changed_by_user_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return PublicAssetUrl::publicStorageUrl($this->logo_path) ?: PublicAssetUrl::fromMedia($this->getFirstMedia('logo'));
    }

    public function getLogoThumbUrlAttribute(): ?string
    {
        return $this->logo_url;
    }

    public function getLogoMediumUrlAttribute(): ?string
    {
        return $this->logo_url;
    }
}
