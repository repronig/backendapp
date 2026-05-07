<?php

namespace Database\Factories;

use App\Models\Member;
use App\Models\MemberProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberProfileFactory extends Factory
{
    protected $model = MemberProfile::class;

    public function definition(): array
    {
        return [
            'member_id' => Member::factory(),
            'date_of_birth' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'occupation' => fake()->jobTitle(),
            'residential_address_line_1' => fake()->streetAddress(),
            'residential_address_line_2' => fake()->optional()->secondaryAddress(),
            'city' => fake()->city(),
            'state' => fake()->state(),
            'country' => 'Nigeria',
            'postal_code' => fake()->postcode(),
            'publisher_name' => fake()->optional()->company(),
            'corporate_name' => fake()->optional()->company(),
        ];
    }
}
