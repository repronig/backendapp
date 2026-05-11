<?php

namespace App\Models;

use App\Enums\SupportTicketCategory;
use App\Enums\SupportTicketPortalContext;
use App\Enums\SupportTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use HasFactory;

    /** SPA path for a specific ticket (e.g. `/member/support/42`). */
    public static function portalTicketDetailPath(string $supportListPath, int $ticketId): string
    {
        return rtrim($supportListPath, '/').'/'.$ticketId;
    }

    /**
     * Human-facing ticket reference: `#` plus the id left-padded with zeros so there are
     * at least three leading zeros before the numeric digits (e.g. `#0005`, `#00042`, `#001234`).
     */
    public static function formattedReference(int $id): string
    {
        $s = (string) max(0, $id);

        return '#'.str_pad($s, \strlen($s) + 3, '0', STR_PAD_LEFT);
    }

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
    ];

    protected $fillable = [
        'user_id',
        'portal_context',
        'subject',
        'body',
        'category',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'portal_context' => SupportTicketPortalContext::class,
            'category' => SupportTicketCategory::class,
            'status' => SupportTicketStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('id');
    }

    public function internalNotes(): HasMany
    {
        return $this->hasMany(SupportTicketInternalNote::class)->orderByDesc('id');
    }
}
