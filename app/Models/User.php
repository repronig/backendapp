<?php

namespace App\Models;

use App\Services\Mail\MailService;
use App\Support\PublicAssetUrl;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'first_name',
    'middle_name',
    'last_name',
    'external_id',
    'email',
    'phone',
    'nationality',
    'password',
    'admin_pin_hash',
    'account_type',
    'status',
    'email_verified_at',
    'avatar_path',
    'requires_two_factor',
    'two_factor_confirmed_at',
    'last_security_confirmation_at',
])]
#[Hidden(['password', 'admin_pin_hash', 'remember_token'])]
class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasRoles, InteractsWithMedia, Notifiable;

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            $user->external_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin_pin_hash' => 'hashed',
            'last_login_at' => 'datetime',
            'requires_two_factor' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
            'last_security_confirmation_at' => 'datetime',
        ];
    }

    protected function name(): Attribute
    {
        return Attribute::get(function (): string {
            return trim(implode(' ', array_filter([$this->first_name, $this->middle_name, $this->last_name]))) ?: $this->email;
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function memberApplication(): HasOne
    {
        return $this->hasOne(MemberApplication::class);
    }

    public function member(): HasOne
    {
        return $this->hasOne(Member::class);
    }

    public function institutionUsers(): HasMany
    {
        return $this->hasMany(InstitutionUser::class);
    }

    public function primaryInstitutionUser(): HasOne
    {
        return $this->hasOne(InstitutionUser::class)
            ->where('is_primary', true)
            ->where('is_active', true);
    }

    public function associations(): BelongsToMany
    {
        return $this->belongsToMany(Association::class, 'association_user', 'user_id', 'association_id')
            ->withPivot(['designation_title', 'is_active'])
            ->withTimestamps();
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class);
    }

    public function uploadedDocuments(): HasMany
    {
        return $this->hasMany(Document::class, 'uploaded_by_user_id');
    }

    public function primaryAssociation(): ?Association
    {
        return $this->associations()->wherePivot('is_active', true)->first();
    }

    public function getAvatarUrlAttribute(): ?string
    {
        $disk = (string) config('media-library.disk_name', config('filesystems.default', 'local'));

        if (filled($this->avatar_path)) {
            try {
                return Storage::disk($disk)->url($this->avatar_path);
            } catch (\Throwable) {
                // Fall back to existing public/media resolution when S3 URL generation is unavailable.
            }
        }

        return PublicAssetUrl::publicStorageUrl($this->avatar_path) ?: PublicAssetUrl::fromMedia($this->getFirstMedia('avatar'));
    }

    public function getAvatarThumbUrlAttribute(): ?string
    {
        return $this->avatar_url;
    }

    public function getAvatarMediumUrlAttribute(): ?string
    {
        return $this->avatar_url;
    }

    public function sendPasswordResetNotification($token): void
    {
        app(MailService::class)->sendPasswordReset($this, (string) $token);
    }

    public function sendEmailVerificationNotification(): void
    {
        app(MailService::class)->sendEmailVerification($this);
    }

    /**
     * Active staff who receive global admin alerts (email + in-app notifications).
     *
     * Includes users with `admin` / `super_admin` Spatie roles and users whose
     * {@see $account_type} is `admin` or `super_admin`, so delivery still works if
     * role pivots are missing or out of sync.
     */
    public static function adminAlertRecipients(): Collection
    {
        $guard = (string) config('auth.defaults.guard', 'web');

        return static::query()
            ->where('status', 'active')
            ->where(function (Builder $query) use ($guard): void {
                $query->whereIn('account_type', ['admin', 'super_admin'])
                    ->orWhereHas('roles', function (Builder $roles) use ($guard): void {
                        $roles->where('guard_name', $guard)
                            ->whereIn('name', ['admin', 'super_admin']);
                    });
            })
            ->orderBy('id')
            ->get()
            ->unique('id')
            ->values();
    }
}
