<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DemoAggregateSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DemoUsersSeeder::class,
            DemoDataSeeder::class,
        ]);
    }
}
