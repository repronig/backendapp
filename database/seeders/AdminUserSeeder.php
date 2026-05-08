<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::query()->firstOrCreate(
            ['email' => 'superadmin@repronig.org'],
            [
                'first_name' => 'Tosin',
                'last_name' => 'Akeredolu',
                'phone' => '+2348000000001',
                'password' => 'RUI9elZRrP-#VBr5UzSp6vhp@!!v?3',
                'admin_pin_hash' => Hash::make('185161'),
                'account_type' => 'super_admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->forceFill(['admin_pin_hash' => $superAdmin->admin_pin_hash ?: Hash::make('185161')])->save();
        $superAdmin->syncRoles(['super_admin']);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@repronig.org'],
            [
                'first_name' => 'Bayo',
                'last_name' => 'Gbadega',
                'phone' => '+2348000000002',
                'password' => 'RUI9elZRrP#DVBr5UzSp6vhp@?1',
                'admin_pin_hash' => Hash::make('185161'),
                'account_type' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        $admin->forceFill(['admin_pin_hash' => $admin->admin_pin_hash ?: Hash::make('185161')])->save();
        $admin->syncRoles(['admin']);
    }
}
