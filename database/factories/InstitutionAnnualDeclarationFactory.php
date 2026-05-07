<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionAnnualDeclaration;
use Illuminate\Database\Eloquent\Factories\Factory;

class InstitutionAnnualDeclarationFactory extends Factory
{
    protected $model = InstitutionAnnualDeclaration::class;

    public function definition(): array
    {
        $basisType = fake()->randomElement(['per_student', 'per_member', 'per_branch', 'flat_rate']);
        $declaredUnits = $basisType === 'flat_rate' ? 1 : fake()->numberBetween(10, 5000);
        $unitCost = $basisType === 'flat_rate' ? null : fake()->randomElement([1000, 2000, 3000]);
        $flatAmount = $basisType === 'flat_rate' ? fake()->randomFloat(2, 100000, 5000000) : null;
        $expectedAmount = $basisType === 'flat_rate' ? $flatAmount : $declaredUnits * $unitCost;

        return [
            'institution_id' => Institution::factory(),
            'licence_id_snapshot' => 'RL-'.fake()->unique()->bothify('##########??'),
            'licensing_year' => (int) now()->format('Y'),
            'basis_type' => $basisType,
            'declared_units' => $declaredUnits,
            'declared_students_count' => $basisType === 'per_student' ? $declaredUnits : null,
            'declared_members_count' => $basisType === 'per_member' ? $declaredUnits : null,
            'declared_branches_count' => $basisType === 'per_branch' ? $declaredUnits : null,
            'declared_faculties_count' => $basisType === 'per_student' ? fake()->numberBetween(1, 12) : null,
            'pricing_unit_cost' => $unitCost,
            'pricing_flat_amount' => $flatAmount,
            'expected_amount' => $expectedAmount,
            'paid_amount' => 0,
            'outstanding_amount' => $expectedAmount,
            'declaration_status' => 'draft',
            'submitted_at' => null,
            'approved_at' => null,
            'approved_by_user_id' => null,
            'invoice_due_date' => now()->addDays(30)->toDateString(),
            'supporting_document_path' => null,
            'supporting_document_disk' => 'public',
            'supporting_document_name' => null,
            'supporting_document_mime_type' => null,
            'supporting_document_size' => null,
            'metadata_json' => null,
        ];
    }

    public function withSupportingDocument(): static
    {
        return $this->state(fn () => [
            'supporting_document_path' => 'declarations/demo-supporting-document.pdf',
            'supporting_document_disk' => 'public',
            'supporting_document_name' => 'demo-supporting-document.pdf',
            'supporting_document_mime_type' => 'application/pdf',
            'supporting_document_size' => 262144,
        ]);
    }

    public function submitted(): static
    {
        return $this->withSupportingDocument()->state(fn () => [
            'declaration_status' => 'submitted',
            'submitted_at' => now(),
        ]);
    }

    public function underReview(): static
    {
        return $this->withSupportingDocument()->state(fn () => [
            'declaration_status' => 'under_review',
            'submitted_at' => now()->subDay(),
        ]);
    }

    public function approved(): static
    {
        return $this->withSupportingDocument()->state(fn (array $attributes) => [
            'declaration_status' => 'approved',
            'submitted_at' => now()->subDay(),
            'approved_at' => now(),
            'outstanding_amount' => $attributes['expected_amount'] ?? 0,
        ]);
    }

    public function academic(): static
    {
        return $this->state([
            'basis_type' => 'per_student',
            'declared_units' => 5000,
            'declared_students_count' => 5000,
            'declared_members_count' => null,
            'declared_branches_count' => null,
            'declared_faculties_count' => 5,
            'pricing_unit_cost' => 1000,
            'pricing_flat_amount' => null,
            'expected_amount' => 5000000,
            'paid_amount' => 0,
            'outstanding_amount' => 5000000,
        ]);
    }

    public function tertiary(): static
    {
        return $this->academic();
    }

    public function nonAcademic(): static
    {
        return $this->state([
            'basis_type' => 'per_member',
            'declared_units' => 2500,
            'declared_students_count' => null,
            'declared_members_count' => 2500,
            'declared_branches_count' => 10,
            'declared_faculties_count' => null,
            'pricing_unit_cost' => 1000,
            'pricing_flat_amount' => null,
            'expected_amount' => 2500000,
            'paid_amount' => 0,
            'outstanding_amount' => 2500000,
        ]);
    }
}
