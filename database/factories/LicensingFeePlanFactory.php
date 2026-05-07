<?php

namespace Database\Factories;

use App\Models\LicensingFeePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

class LicensingFeePlanFactory extends Factory
{
    protected $model = LicensingFeePlan::class;

    public function definition(): array
    {
        $institutionType = fake()->randomElement([
            'university',
            'polytechnic',
            'college_of_education',
            'professional_body',
            'religious_organization',
            'corporate_organization',
            'government_agency',
            'ngo',
            'research_institute',
            'library',
            'other',
        ]);

        $basisType = match ($institutionType) {
            'university', 'polytechnic', 'college_of_education', 'research_institute' => 'per_student',
            'professional_body' => 'per_member',
            'religious_organization' => 'per_branch',
            default => 'flat_rate',
        };

        return [
            'institution_type' => $institutionType,
            'basis_type' => $basisType,
            'unit_cost' => in_array($basisType, ['per_student', 'per_member', 'per_branch'], true)
                ? fake()->randomElement([1000, 2000, 3000])
                : null,
            'flat_amount' => $basisType === 'flat_rate'
                ? fake()->randomFloat(2, 100000, 5000000)
                : null,
            'effective_from_year' => (int) now()->format('Y'),
            'effective_to_year' => null,
            'is_active' => true,
            'description' => fake()->sentence(),
            'metadata_json' => null,
        ];
    }
}
