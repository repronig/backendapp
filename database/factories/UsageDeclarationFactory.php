<?php

namespace Database\Factories;

use App\Models\Licence;
use App\Models\UsageDeclaration;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UsageDeclarationFactory extends Factory
{
    protected $model = UsageDeclaration::class;

    public function definition(): array
    {
        $licence = Licence::factory()->create();

        return [
            'licence_id' => $licence->id,
            'institution_id' => $licence->institution_id,
            'reporting_year' => (int) $licence->licence_year,
            'declaration_status' => fake()->randomElement(['draft', 'submitted', 'reviewed', 'rejected']),
            'submitted_by_user_id' => User::factory(),
            'declared_student_population' => fake()->numberBetween(1000, 50000),
            'declared_academic_staff_count' => fake()->numberBetween(30, 2000),
            'declared_administrative_staff_count' => fake()->numberBetween(20, 2000),
            'declared_campuses_count' => fake()->numberBetween(1, 10),
            'declared_library_capacity' => fake()->numberBetween(500, 100000),
            'declaration_notes' => fake()->optional()->sentence(),
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by_user_id' => null,
        ];
    }
}
