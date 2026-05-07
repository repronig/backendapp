<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AccessControlSeeder::class,
            ReferenceDataSeeder::class,
            LanguageSeeder::class,
            AutomationDefinitionSeeder::class,
            DemoAggregateSeeder::class,
        ]);
    }
}
