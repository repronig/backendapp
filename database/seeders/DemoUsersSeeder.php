<?php

namespace Database\Seeders;

use App\Models\Association;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {

            //NPA
            $association_npa = Association::query()->where('code', 'NPA')->firstOrFail();

            $officer_npa = User::query()->updateOrCreate(
                ['email' => 'info@npa.org'],
                [
                    'first_name' => 'Adewale',
                    'last_name' => 'Adeyemi',
                    'phone' => '+2348000000011',
                    'password' => Hash::make('NPA@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_npa->syncRoles(['association_officer']);

            $association_npa->users()->syncWithoutDetaching([
                $officer_npa->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);


            // ANA — must match `AssociationsSeeder` / DB `associations.code` (PostgreSQL is case-sensitive)
            $association_ana = Association::query()->where('code', 'ANA')->firstOrFail();

            $officer_ana = User::query()->updateOrCreate(
                ['email' => 'info@ana.org'],
                [
                    'first_name' => 'Ngozi',
                    'last_name' => 'Chima',
                    'phone' => '+2348000000012',
                    'password' => Hash::make('ANA@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_ana->syncRoles(['association_officer']);

            $association_ana->users()->syncWithoutDetaching([
                $officer_ana->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);

            // AANFAN
            $association_aanfan = Association::query()->where('code', 'AANFAN')->firstOrFail();

            $officer_aanfan = User::query()->updateOrCreate(
                ['email' => 'info@anfaan.org'],
                [
                    'first_name' => 'Gbenga',
                    'last_name' => 'Kolawole',
                    'phone' => '+2348000000013',
                    'password' => Hash::make('AANFAN@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_aanfan->syncRoles(['association_officer']);

            $association_aanfan->users()->syncWithoutDetaching([
                $officer_aanfan->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);


            // SNA
            $association_sna = Association::query()->where('code', 'SNA')->firstOrFail();

            $officer_sna = User::query()->updateOrCreate(
                ['email' => 'info@sna.org'],
                [
                    'first_name' => 'Noah',
                    'last_name' => 'Adetayo',
                    'phone' => '+2348000000014',
                    'password' => Hash::make('SNA@2026!rep'),
                    'account_type' => 'association_officer',
                    'status' => 'active',
                    'email_verified_at' => now(),
                ]
            );
            $officer_sna->syncRoles(['association_officer']);

            $association_sna->users()->syncWithoutDetaching([
                $officer_sna->id => [
                    'designation_title' => 'Secretary',
                    'is_active' => true,
                ],
            ]);

        });
    }
}
