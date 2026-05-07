<?php

namespace Database\Factories;

use App\Models\InstitutionAnnualDeclaration;
use App\Models\InstitutionDeclarationFaculty;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionDeclarationFaculty>
 */
class InstitutionDeclarationFacultyFactory extends Factory
{
    protected $model = InstitutionDeclarationFaculty::class;

    public function definition(): array
    {
        return [
            'institution_annual_declaration_id' => InstitutionAnnualDeclaration::factory(),
            'faculty_name' => 'Faculty of ' . fake()->randomElement([
                'Arts',
                'Science',
                'Law',
                'Education',
                'Engineering',
                'Social Sciences',
            ]),
            'student_count' => fake()->numberBetween(50, 5000),
            'sort_order' => fake()->numberBetween(1, 20),
        ];
    }
}