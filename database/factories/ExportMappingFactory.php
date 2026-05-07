<?php

namespace Database\Factories;

use App\Models\ExportMapping;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ExportMapping>
 */
class ExportMappingFactory extends Factory
{
    protected $model = ExportMapping::class;

    public function definition(): array
    {
        $domain = fake()->randomElement([
            'members',
            'works',
            'institutions',
            'licences',
            'payments',
        ]);

        return [
            'domain' => $domain,
            'mapping_key' => $domain . '.' . fake()->unique()->slug(2),
            'mapping_json' => [
                'source_field' => fake()->randomElement(['name', 'email', 'title', 'status']),
                'target_field' => fake()->randomElement(['external_name', 'external_email', 'external_title', 'external_status']),
                'transform' => fake()->randomElement(['none', 'uppercase', 'trim']),
            ],
            'is_active' => fake()->boolean(85),
        ];
    }
}