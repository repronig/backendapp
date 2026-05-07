<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionProfile>
 */
class InstitutionProfileFactory extends Factory
{
    protected $model = InstitutionProfile::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory()->state(['institution_type' => 'university']),
            'academic_staff_count' => fake()->numberBetween(10, 1000),
            'administrative_staff_count' => fake()->numberBetween(5, 500),
            'campuses_count' => fake()->numberBetween(1, 10),
            'metadata_json' => [
                'library_capacity' => fake()->numberBetween(100, 5000),
                'student_population' => fake()->numberBetween(200, 50000),
            ],
        ];
    }

    public function nonAcademic(): self
    {
        return $this->state(fn () => [
            'academic_staff_count' => null,
            'administrative_staff_count' => null,
            'campuses_count' => null,
            'metadata_json' => [
                'library_capacity' => fake()->numberBetween(100, 5000),
                'student_population' => null,
            ],
        ]);
    }
}
