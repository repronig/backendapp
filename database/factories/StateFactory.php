<?php

namespace Database\Factories;

use App\Models\State;
use Illuminate\Database\Eloquent\Factories\Factory;

class StateFactory extends Factory
{
    protected $model = State::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->state(),
            'code' => strtoupper(fake()->unique()->lexify('??')),
            'country_code' => 'NG',
        ];
    }
}
