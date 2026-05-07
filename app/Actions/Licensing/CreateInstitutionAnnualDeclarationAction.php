<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\DeclarationStatus;
use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateInstitutionAnnualDeclarationAction
{
    public function __construct(
        protected CalculateInstitutionDeclarationExpectedAmountAction $calculateInstitutionDeclarationExpectedAmountAction,
        protected LogAuditAction $logAuditAction
    ) {}

    public function execute(
        Institution $institution,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        string $status = DeclarationStatus::Draft->value
    ): InstitutionAnnualDeclaration {
        return DB::transaction(function () use ($institution, $data, $actor, $ipAddress, $userAgent, $status) {
            $licensingYear = (int) ($data['licensing_year'] ?? now()->year);
            $existingDeclaration = InstitutionAnnualDeclaration::query()
                ->where('institution_id', $institution->id)
                ->where('licensing_year', $licensingYear)
                ->first();
            if ($existingDeclaration) {
                throw ValidationException::withMessages([
                    'licensing_year' => ['A declaration already exists for this licensing year.'],
                ]);
            }
            $calculated = $this->calculateInstitutionDeclarationExpectedAmountAction->execute($institution, $data, $licensingYear);
            $academicTypes = ['university', 'polytechnic', 'college_of_education', 'research_institute'];
            $usesAcademicDeclaration = in_array($institution->institution_type, $academicTypes, true);
            $faculties = $usesAcademicDeclaration ? ($data['faculties'] ?? []) : [];
            $declaredStudentsCount = $usesAcademicDeclaration
                ? ($data['declared_students_count'] ?? collect($faculties)->sum(fn (array $faculty): int => (int) ($faculty['student_count'] ?? 0)))
                : null;

            $payload = [
                'licence_id_snapshot' => $institution->licence_id,
                'basis_type' => $calculated['basis_type'],
                'declared_units' => $calculated['declared_units'],
                'declared_students_count' => $usesAcademicDeclaration ? ($declaredStudentsCount ?: null) : null,
                'declared_members_count' => $usesAcademicDeclaration ? null : ($data['declared_members_count'] ?? null),
                'declared_branches_count' => $usesAcademicDeclaration ? null : ($data['declared_branches_count'] ?? null),
                'declared_faculties_count' => $usesAcademicDeclaration ? count($faculties) : null,
                'pricing_unit_cost' => $calculated['pricing_unit_cost'],
                'pricing_flat_amount' => $calculated['pricing_flat_amount'],
                'expected_amount' => $calculated['expected_amount'],
                'paid_amount' => 0,
                'outstanding_amount' => $calculated['expected_amount'],
                'declaration_status' => $status,
                'submitted_at' => $status === DeclarationStatus::Submitted->value ? now() : null,
                'metadata_json' => $data['metadata_json'] ?? null,
            ];

            if (($data['supporting_document'] ?? null) instanceof UploadedFile) {
                $file = $data['supporting_document'];
                $payload += [
                    'supporting_document_path' => $file->store('declarations', 'public'),
                    'supporting_document_disk' => 'public',
                    'supporting_document_name' => $file->getClientOriginalName(),
                    'supporting_document_mime_type' => $file->getClientMimeType(),
                    'supporting_document_size' => $file->getSize(),
                ];
            }

            $declaration = InstitutionAnnualDeclaration::create([
                'institution_id' => $institution->id,
                'licensing_year' => $licensingYear,
                ...$payload,
            ]);

            $declaration->faculties()->delete();
            foreach ($faculties as $index => $faculty) {
                $declaration->faculties()->create([
                    'faculty_name' => $faculty['faculty_name'],
                    'student_count' => $faculty['student_count'],
                    'sort_order' => $index + 1,
                ]);
            }

            $fresh = $declaration->fresh(['faculties', 'licence']);

            $this->logAuditAction->execute(
                $actor,
                'institution_annual_declaration_created',
                $fresh,
                null,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}
