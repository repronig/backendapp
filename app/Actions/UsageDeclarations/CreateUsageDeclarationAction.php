<?php

namespace App\Actions\UsageDeclarations;

use App\Actions\Audit\LogAuditAction;
use App\Models\Licence;
use App\Models\UsageDeclaration;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateUsageDeclarationAction
{
    public function __construct(
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        Licence $licence,
        User $submittedBy,
        array $data,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): UsageDeclaration {
        $exists = UsageDeclaration::where('licence_id', $licence->id)
            ->where('reporting_year', $data['reporting_year'])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'reporting_year' => [
                    'A usage declaration already exists for this licence and reporting year.',
                ],
            ]);
        }

        $declaration = UsageDeclaration::create([
            'licence_id' => $licence->id,
            'institution_id' => $licence->institution_id,
            'reporting_year' => $data['reporting_year'],
            'declaration_status' => 'submitted',
            'submitted_by_user_id' => $submittedBy->id,
            'declared_student_population' => $data['declared_student_population'] ?? null,
            'declared_academic_staff_count' => $data['declared_academic_staff_count'] ?? null,
            'declared_administrative_staff_count' => $data['declared_administrative_staff_count'] ?? null,
            'declared_campuses_count' => $data['declared_campuses_count'] ?? null,
            'declared_library_capacity' => $data['declared_library_capacity'] ?? null,
            'declaration_notes' => $data['declaration_notes'] ?? null,
            'submitted_at' => now(),
        ]);

        $fresh = $declaration->fresh();

        $this->logAuditAction->execute(
            $submittedBy,
            'usage_declaration_created',
            $fresh,
            null,
            $fresh->toArray(),
            $ipAddress,
            $userAgent
        );

        return $fresh;
    }
}