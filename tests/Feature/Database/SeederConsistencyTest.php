<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

it('seeds the main roles and permissions needed by all portals', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    foreach (['member', 'association_officer', 'institution_user', 'admin', 'super_admin'] as $role) {
        expect(Role::where('name', $role)->exists())->toBeTrue();
    }

    foreach ([
        'create own member application',
        'create own works',
        'create own annual declarations',
        'view members',
        'review works admin',
        'manage role permissions',
        'create languages',
    ] as $permission) {
        expect(Permission::where('name', $permission)->exists())->toBeTrue();
    }
});
