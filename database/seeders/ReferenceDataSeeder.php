<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ReferenceDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PlatformSettingsSeeder::class,
            NigerianStatesSeeder::class,
            NigerianCitiesSeeder::class,
            AssociationsSeeder::class,
            LicensingFeePlanSeeder::class,
        ]); 
    }
}
