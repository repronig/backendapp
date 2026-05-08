<?php

namespace App\Actions\Licensing;

use App\Actions\Audit\LogAuditAction;
use App\Enums\DeclarationStatus;
use App\Models\InstitutionAnnualDeclaration;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class UpdateInstitutionAnnualDeclarationAction
{
    public function __construct(
        protected CalculateInstitutionDeclarationExpectedAmountAction $calculateInstitutionDeclarationExpectedAmountAction,
        protected LogAuditAction $logAuditAction
    ) {
    }

    public function execute(
        InstitutionAnnualDeclaration $declaration,
        array $data,
        ?User $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): InstitutionAnnualDeclaration {
        return DB::transaction(function () use ($declaration, $data, $actor, $ipAddress, $userAgent) {
            if ($declaration->declaration_status !== DeclarationStatus::Draft->value) {
                throw ValidationException::withMessages([
                    'declaration_status' => ['Only draft declarations can be modified.'],
                ]);
            }

            $before = $declaration->load('faculties')->toArray();

            $calculated = $this->calculateInstitutionDeclarationExpectedAmountAction->execute(
                $declaration->institution,
                $data,
                (int) $declaration->licensing_year
            );
            $academicTypes = ['university', 'polytechnic', 'college_of_education', 'research_institute'];
            $usesAcademicDeclaration = in_array($declaration->institution->institution_type, $academicTypes, true);
            $faculties = $usesAcademicDeclaration ? ($data['faculties'] ?? []) : [];
            $declaredStudentsCount = $usesAcademicDeclaration
                ? ($data['declared_students_count'] ?? collect($faculties)->sum(fn (array $faculty): int => (int) ($faculty['student_count'] ?? 0)))
                : null;

            $payload = [
                'basis_type' => $calculated['basis_type'],
                'declared_units' => $calculated['declared_units'],
                'declared_students_count' => $usesAcademicDeclaration ? ($declaredStudentsCount ?: null) : null,
                'declared_members_count' => $usesAcademicDeclaration ? null : ($data['declared_members_count'] ?? null),
                'declared_branches_count' => $usesAcademicDeclaration ? null : ($data['declared_branches_count'] ?? null),
                'declared_faculties_count' => $usesAcademicDeclaration ? count($faculties) : null,
                'pricing_unit_cost' => $calculated['pricing_unit_cost'],
                'pricing_flat_amount' => $calculated['pricing_flat_amount'],
                'expected_amount' => $calculated['expected_amount'],
                'outstanding_amount' => max($calculated['expected_amount'] - (float) $declaration->paid_amount, 0),
                'metadata_json' => $data['metadata_json'] ?? $declaration->metadata_json,
            ];

            if (! $declaration->supporting_document_path && ! (($data['supporting_document'] ?? null) instanceof UploadedFile)) {
                throw ValidationException::withMessages([
                    'supporting_document' => ['Upload a supporting document before saving this declaration.'],
                ]);
            }

            if (($data['supporting_document'] ?? null) instanceof UploadedFile) {
                $defaultDisk = (string) config('filesystems.default', 'local');
                if ($declaration->supporting_document_path) {
                    Storage::disk($declaration->supporting_document_disk ?: $defaultDisk)->delete($declaration->supporting_document_path);
                }

                $file = $data['supporting_document'];
                $payload += [
                    'supporting_document_path' => $file->store('declarations', $defaultDisk),
                    'supporting_document_disk' => $defaultDisk,
                    'supporting_document_name' => $file->getClientOriginalName(),
                    'supporting_document_mime_type' => $file->getClientMimeType(),
                    'supporting_document_size' => $file->getSize(),
                ];
            }

            $declaration->update($payload);

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
                'institution_annual_declaration_updated',
                $fresh,
                $before,
                $fresh->toArray(),
                $ipAddress,
                $userAgent
            );

            return $fresh;
        });
    }
}
