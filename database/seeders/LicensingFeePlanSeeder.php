<?php

namespace Database\Seeders;

use App\Models\LicensingFeePlan;
use Illuminate\Database\Seeder;

class LicensingFeePlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            ['institution_type' => 'university', 'basis_type' => 'per_student', 'unit_cost' => 1000],
            ['institution_type' => 'polytechnic', 'basis_type' => 'per_student', 'unit_cost' => 800],
            ['institution_type' => 'college_of_education', 'basis_type' => 'per_student', 'unit_cost' => 700],
            ['institution_type' => 'research_institute', 'basis_type' => 'per_student', 'unit_cost' => 900],
            ['institution_type' => 'professional_body', 'basis_type' => 'per_member', 'unit_cost' => 1500],
            ['institution_type' => 'corporate_organization', 'basis_type' => 'per_member', 'unit_cost' => 1200],
            ['institution_type' => 'government_agency', 'basis_type' => 'per_member', 'unit_cost' => 1000],
            ['institution_type' => 'ngo', 'basis_type' => 'per_member', 'unit_cost' => 750],
            ['institution_type' => 'religious_organization', 'basis_type' => 'per_branch', 'unit_cost' => 50000],
            ['institution_type' => 'library', 'basis_type' => 'flat_rate', 'flat_amount' => 250000],
            ['institution_type' => 'other', 'basis_type' => 'flat_rate', 'flat_amount' => 150000],
        ];

        foreach ($plans as $plan) {
            LicensingFeePlan::query()->updateOrCreate(
                [
                    'institution_type' => $plan['institution_type'],
                    'basis_type' => $plan['basis_type'],
                    'effective_from_year' => 2026,
                ],
                [
                    'unit_cost' => $plan['unit_cost'] ?? null,
                    'flat_amount' => $plan['flat_amount'] ?? null,
                    'effective_to_year' => null,
                    'is_active' => true,
                    'description' => 'Seeded default fee plan for '.str_replace('_', ' ', $plan['institution_type']),
                    'metadata_json' => ['seeded' => true],
                ]
            );
        }
    }
}
