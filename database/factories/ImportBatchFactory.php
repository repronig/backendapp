<?php

namespace Database\Factories;

use App\Models\ImportBatch;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ImportBatch>
 */
class ImportBatchFactory extends Factory
{
    protected $model = ImportBatch::class;

    public function definition(): array
    {
        $totalRows = fake()->numberBetween(10, 500);
        $invalidRows = fake()->numberBetween(0, max(1, (int) floor($totalRows * 0.2)));
        $validRows = $totalRows - $invalidRows;
        $processedRows = fake()->numberBetween(0, $totalRows);
        $status = fake()->randomElement(['pending', 'validated', 'processing', 'processed', 'failed']);

        return [
            'created_by_user_id' => User::factory(),
            'import_type' => fake()->randomElement([
                'members',
                'works',
                'institutions',
            ]),
            'status' => $status,
            'total_rows' => $totalRows,
            'valid_rows' => $validRows,
            'invalid_rows' => $invalidRows,
            'processed_rows' => $processedRows,
            'source_filename' => fake()->slug() . '.csv',
            'error_report_path' => $invalidRows > 0 ? 'imports/errors/' . fake()->uuid() . '.csv' : null,
            'summary_json' => [
                'status' => $status,
                'warnings' => fake()->boolean(30) ? [fake()->sentence()] : [],
            ],
            'validated_at' => in_array($status, ['validated', 'processing', 'processed', 'failed'], true)
                ? fake()->dateTimeBetween('-10 days', 'now')
                : null,
            'processed_at' => in_array($status, ['processed', 'failed'], true)
                ? fake()->dateTimeBetween('-5 days', 'now')
                : null,
        ];
    }
}