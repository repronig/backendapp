<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\ImportRowFailure;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportRowFailure>
 */
class ImportRowFailureFactory extends Factory
{
    protected $model = ImportRowFailure::class;

    public function definition(): array
    {
        return [
            'import_batch_id' => ImportBatch::factory(),
            'row_number' => fake()->numberBetween(1, 500),
            'row_payload_json' => [
                'email' => fake()->safeEmail(),
                'name' => fake()->name(),
                'status' => fake()->word(),
            ],
            'errors_json' => [
                'email' => ['The email has already been taken.'],
            ],
        ];
    }
}