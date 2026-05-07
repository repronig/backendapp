<?php

namespace Database\Factories;

use App\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    private const ACADEMIC_INSTITUTION_TYPES = [
        'university',
        'polytechnic',
        'college_of_education',
        'research_institute',
    ];

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
        $usesAcademicMetrics = in_array($institutionType, self::ACADEMIC_INSTITUTION_TYPES, true);

        return [
            'external_id' => (string) Str::uuid(),
            'name' => fake()->company(),
            'institution_type' => $institutionType,
            'registration_number' => fake()->optional()->bothify('REG-####-??'),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+234'.fake()->numerify('80########'),
            'contact_person_name' => fake()->name(),
            'contact_person_title' => fake()->jobTitle(),
            'faculties_count' => $usesAcademicMetrics ? fake()->numberBetween(1, 12) : null,
            'member_count' => $usesAcademicMetrics ? null : fake()->numberBetween(50, 10000),
            'branches_count' => $usesAcademicMetrics ? null : fake()->numberBetween(1, 10),
            'onboarding_status' => 'approved',
            'account_status' => 'active',
            'governance_status' => 'normal',
            'address_line_1' => fake()->streetAddress(),
            'address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Nigeria',
            'postal_code' => fake()->postcode(),
            'approved_at' => now()->subDays(10),
            'licence_id' => 'RL-'.strtoupper(fake()->bothify('##########??')),
            'licence_id_generated_at' => now()->subDays(7),
        ];
    }
}
