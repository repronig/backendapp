<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LicencePayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'licence_id',
        'institution_id',
        'institution_annual_declaration_id',
        'invoice_id',
        'payment_reference',
        'idempotency_key',
        'gateway_reference',
        'provider_event_id',
        'gateway_name',
        'amount',
        'amount_allocated',
        'balance_before',
        'balance_after',
        'currency',
        'payment_status',
        'paid_at',
        'processed_at',
        'is_reconciled',
        'reconciled_at',
        'reconciled_by_user_id',
        'raw_response_json',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_allocated' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'paid_at' => 'datetime',
        'processed_at' => 'datetime',
        'reconciled_at' => 'datetime',
        'is_reconciled' => 'boolean',
        'raw_response_json' => 'array',
    ];

    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(InstitutionAnnualDeclaration::class, 'institution_annual_declaration_id');
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(PaymentReconciliation::class);
    }

    public function reconciledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reconciled_by_user_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
