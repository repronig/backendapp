<?php

namespace App\Models;

use App\Enums\ComplianceAssessmentType;
use App\Enums\ComplianceOverallStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstitutionComplianceAssessment extends Model
{
    use HasFactory;

    protected $fillable = [
        'institution_id',
        'assessed_at',
        'assessment_type',
        'source_declaration_id',
        'scores',
        'flags',
        'overall_status',
    ];

    protected function casts(): array
    {
        return [
            'assessed_at' => 'datetime',
            'assessment_type' => ComplianceAssessmentType::class,
            'scores' => 'array',
            'flags' => 'array',
            'overall_status' => ComplianceOverallStatus::class,
        ];
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function sourceDeclaration(): BelongsTo
    {
        return $this->belongsTo(InstitutionAnnualDeclaration::class, 'source_declaration_id');
    }
}
