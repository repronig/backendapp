<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Licence extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'institution_annual_declaration_id',
        'licence_number',
        'licence_id_snapshot',
        'licence_year',
        'agreement_version',
        'licence_status',
        'payment_status',
        'start_date',
        'end_date',
        'negotiated_rate',
        'amount_due',
        'amount_paid',
        'outstanding_amount',
        'issued_by_user_id',
        'issued_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'negotiated_rate' => 'decimal:2',
        'amount_due' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'issued_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(InstitutionAnnualDeclaration::class, 'institution_annual_declaration_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LicencePayment::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
