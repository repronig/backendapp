<?php

namespace Database\Factories;

use App\Models\City;
use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<City>
 */
class CityFactory extends Factory
{
    protected $model = City::class;

    public function definition(): array
    {
        return [
            'state_id' => State::query()->inRandomOrder()->value('id')
                ?? State::query()->create([
                    'name' => fake()->state(),
                    'code' => strtoupper(fake()->unique()->lexify('??')),
                    'country_code' => 'NG',
                ])->id,
            'name' => fake()->unique()->city(),
        ];
    }
}