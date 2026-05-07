<?php

namespace Database\Factories;

use App\Models\SavedReportingPeriod;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SavedReportingPeriod>
 */
class SavedReportingPeriodFactory extends Factory
{
    protected $model = SavedReportingPeriod::class;

    public function definition(): array
    {
        $from = fake()->dateTimeBetween('-2 years', '-6 months');
        $to = (clone $from)->modify('+' . fake()->numberBetween(30, 180) . ' days');

        return [
            'created_by_user_id' => fake()->boolean(80) ? User::factory() : null,
            'name' => fake()->randomElement([
                'Last Quarter',
                'Current Financial Year',
                'Annual Licensing Window',
                'Custom Audit Range',
            ]) . ' ' . fake()->numerify('##'),
            'date_from' => $from->format('Y-m-d'),
            'date_to' => $to->format('Y-m-d'),
            'filters_json' => [
                'association_id' => null,
                'institution_type' => null,
                'status' => fake()->randomElement(['active', 'pending', 'all']),
            ],
        ];
    }
}