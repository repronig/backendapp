<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionDeclarationFaculty extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_annual_declaration_id',
        'faculty_name',
        'student_count',
        'sort_order',
    ];

    protected $casts = [
        'student_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function declaration(): BelongsTo
    {
        return $this->belongsTo(InstitutionAnnualDeclaration::class, 'institution_annual_declaration_id');
    }
}
