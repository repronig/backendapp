<?php

namespace Database\Factories;

use App\Models\Setting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Setting>
 */
class SettingFactory extends Factory
{
    protected $model = Setting::class;

    public function definition(): array
    {
        return [
            'group' => Setting::GROUP_GENERAL,
            'key' => 'system.'.fake()->unique()->slug(2, '_'),
            'value' => [
                'enabled' => fake()->boolean(),
                'label' => fake()->words(2, true),
                'updated_by' => 'factory',
            ],
        ];
    }
}
