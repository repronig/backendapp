<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'institution_id',
        'institution_annual_declaration_id',
        'licence_id',
        'invoice_type',
        'billing_year',
        'issue_date',
        'due_date',
        'subtotal_amount',
        'total_amount',
        'amount_paid',
        'outstanding_amount',
        'invoice_status',
        'currency',
        'metadata_json',
        'issued_at',
        'paid_at',
        'last_due_reminder_sent_at',
        'last_overdue_reminder_sent_at',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'subtotal_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'metadata_json' => 'array',
        'issued_at' => 'datetime',
        'paid_at' => 'datetime',
        'last_due_reminder_sent_at' => 'datetime',
        'last_overdue_reminder_sent_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(InstitutionAnnualDeclaration::class, 'institution_annual_declaration_id');
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(InvoiceAdjustment::class);
    }

    public function licence(): BelongsTo
    {
        return $this->belongsTo(Licence::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LicencePayment::class);
    }

    public function syncStatus(): void
    {
        $outstanding = max(round((float) $this->total_amount - (float) $this->amount_paid, 2), 0);

        if ($this->invoice_status === InvoiceStatus::Cancelled->value) {
            $status = InvoiceStatus::Cancelled->value;
        } elseif ($outstanding <= 0) {
            $status = InvoiceStatus::Paid->value;
        } elseif ($this->due_date && now()->startOfDay()->gt($this->due_date->startOfDay())) {
            $status = InvoiceStatus::Overdue->value;
        } else {
            $status = (float) $this->amount_paid > 0 ? InvoiceStatus::PartiallyPaid->value : InvoiceStatus::Issued->value;
        }

        $this->forceFill([
            'outstanding_amount' => $outstanding,
            'invoice_status' => $status,
            'paid_at' => $outstanding <= 0 ? ($this->paid_at ?? now()) : null,
        ])->save();
    }
}
