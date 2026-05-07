<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LicensingFeePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_type',
        'basis_type',
        'unit_cost',
        'flat_amount',
        'effective_from_year',
        'effective_to_year',
        'is_active',
        'description',
        'metadata_json',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'flat_amount' => 'decimal:2',
        'effective_from_year' => 'integer',
        'effective_to_year' => 'integer',
        'is_active' => 'boolean',
        'metadata_json' => 'array',
    ];
}
