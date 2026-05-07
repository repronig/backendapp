<?php

namespace App\Models;

use App\Support\PublicAssetUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Association extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $fillable = [
        'external_id',
        'name',
        'code',
        'type',
        'description',
        'contact_email',
        'contact_phone',
        'status',
        'logo_path',
        'address_line_1',
        'address_line_2',
        'state_id',
        'city_id',
        'country',
        'postal_code',
        'is_enabled',
        'disabled_at',
        'disabled_by_user_id',
        'disable_reason',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'disabled_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $association): void {
            $association->external_id ??= (string) Str::uuid();
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')->singleFile();
    }


    public function memberApplications(): HasMany
    {
        return $this->hasMany(MemberApplication::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(Member::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'association_user')
            ->withPivot(['designation_title', 'is_active'])
            ->withTimestamps();
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function disabledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disabled_by_user_id');
    }

    public function scopeEnabled($query)
    {
        return $query->where('is_enabled', true)->where('status', 'active');
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