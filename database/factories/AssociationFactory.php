<?php

namespace Database\Factories;

use App\Models\Association;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AssociationFactory extends Factory
{
    protected $model = Association::class;

    public function definition(): array
    {
        return [
            'external_id' => (string) Str::uuid(),
            'name' => fake()->unique()->company() . ' Association',
            'code' => strtoupper(fake()->unique()->bothify('REP-??#')),
            'type' => fake()->randomElement(['author_association', 'publisher_association', 'writers_association']),
            'description' => fake()->sentence(),
            'contact_email' => fake()->safeEmail(),
            'contact_phone' => '+234' . fake()->numerify('80########'),
            'status' => 'active',
            'country' => 'Nigeria',
            'is_enabled' => true,
        ];
    }
}
