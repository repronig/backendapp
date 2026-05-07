<?php

namespace Database\Factories;

use App\Models\Institution;
use App\Models\InstitutionUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InstitutionUser>
 */
class InstitutionUserFactory extends Factory
{
    protected $model = InstitutionUser::class;

    public function definition(): array
    {
        return [
            'institution_id' => Institution::factory(),
            'user_id' => User::factory()->state([
                'account_type' => 'institution_user',
            ]),
            'role_label' => fake()->randomElement([
                'primary_contact',
                'compliance_officer',
                'finance_contact',
                'registrar',
            ]),
            'is_primary' => false,
            'is_active' => true,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn () => [
            'is_primary' => true,
            'role_label' => 'primary_contact',
        ]);
    }
}