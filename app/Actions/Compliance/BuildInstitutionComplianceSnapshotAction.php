<?php

namespace App\Actions\Compliance;

use App\Enums\ComplianceAssessmentType;
use App\Enums\ComplianceOverallStatus;
use App\Models\Institution;

/**
 * Stub snapshot used later by scheduled jobs or admin endpoints; not wired to HTTP in Phase C.
 */
class BuildInstitutionComplianceSnapshotAction
{
    /**
     * @return array{
     *     institution_id: int,
     *     scores: array<string, mixed>,
     *     flags: list<string>,
     *     overall_status: string,
     *     assessment_type: string,
     *     assessed_at: string,
     * }
     */
    public function execute(Institution $institution): array
    {
        return [
            'institution_id' => $institution->id,
            'scores' => [],
            'flags' => [],
            'overall_status' => ComplianceOverallStatus::Ok->value,
            'assessment_type' => ComplianceAssessmentType::Manual->value,
            'assessed_at' => now()->toIso8601String(),
        ];
    }
}
