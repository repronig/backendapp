<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InstitutionAnnualDeclaration extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'licence_id_snapshot',
        'licensing_year',
        'basis_type',
        'declared_units',
        'declared_students_count',
        'declared_members_count',
        'declared_branches_count',
        'declared_faculties_count',
        'pricing_unit_cost',
        'pricing_flat_amount',
        'expected_amount',
        'paid_amount',
        'outstanding_amount',
        'declaration_status',
        'submitted_at',
        'approved_at',
        'approved_by_user_id',
        'invoice_due_date',
        'supporting_document_path',
        'supporting_document_disk',
        'supporting_document_name',
        'supporting_document_mime_type',
        'supporting_document_size',
        'metadata_json',
    ];

    protected $casts = [
        'pricing_unit_cost' => 'decimal:2',
        'pricing_flat_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'outstanding_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'invoice_due_date' => 'date',
        'metadata_json' => 'array',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function faculties(): HasMany
    {
        return $this->hasMany(InstitutionDeclarationFaculty::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by_user_id');
    }

    public function licence(): HasOne
    {
        return $this->hasOne(Licence::class);
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class, 'institution_annual_declaration_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(LicencePayment::class);
    }
}
