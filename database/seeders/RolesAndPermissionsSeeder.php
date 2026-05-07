<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Permission groups intentionally mirror the current route modules.
     * Keep these names human-readable because Super Admin users manage them in the UI.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $sharedAccountPermissions = [
            'view own account',
            'update own account',
            'change own password',
            'manage own avatar',
            'manage own two factor authentication',
            'confirm own security challenge',
            'manage own security settings',
            'view own security activity',
            'manage own notification preferences',
            'view own notifications',
            'mark own notifications as read',
        ];

        $memberPermissions = [
            ...$sharedAccountPermissions,
            'view own member application',
            'create own member application',
            'update own member application',
            'submit own member application',
            'upload own member application documents',
            'delete own member application documents',
            'view own member profile',
            'update own member profile',
            'view own works',
            'create own works',
            'update own works',
            'submit own works',
            'manage own work contributors',
            'manage own work files',
        ];

        $associationOfficerPermissions = [
            ...$sharedAccountPermissions,
            'view association dashboard',
            'view association profile',
            'update association profile',
            'manage association logo',
            'view association member applications',
            'review association member applications',
            'approve association member applications',
            'reject association member applications',
            'request changes on association member applications',
        ];

        $institutionUserPermissions = [
            ...$sharedAccountPermissions,
            'view institution dashboard',
            'view own institution profile',
            'update own institution profile',
            'manage own institution logo',
            'upload own institution documents',
            'view own annual declarations',
            'create own annual declarations',
            'update own annual declarations',
            'submit own annual declarations',
            'view own licences',
            'initiate own licence payments',
            'verify own licence payments',
            'view own licence payments',
            'view own invoices',
            'initiate own invoice payments',
            'view own usage declarations',
            'create own usage declarations',
        ];

        $adminPermissions = [
            ...$sharedAccountPermissions,
            'view admin dashboard',
            'view finance summary',
            'view members',
            'export members',
            'view member applications admin',
            'view works admin',
            'export works',
            'review works admin',
            'dispute work contributors admin',
            'view institutions admin',
            'export institutions',
            'approve institutions',
            'reject institutions',
            'deactivate institutions',
            'reactivate institutions',
            'view institution declarations admin',
            'export institution declarations',
            'review institution declarations',
            'approve institution declarations',
            'reject institution declarations',
            'view licences admin',
            'export licences',
            'view payments admin',
            'export payments',
            'view invoices admin',
            'create invoice adjustments',
            'view usage declarations admin',
            'view reports',
            'view board reports',
            'view audit logs',
            'view timelines',
            'view admin documents',
            'upload admin documents',
            'delete admin documents',
            'view imports',
            'upload imports',
            'process imports',
            'manage licensing fee plans',
            'manage terms and conditions',
            'view associations admin',
            'export associations admin',
            'view association details admin',
            'enable associations admin',
            'disable associations admin',
            'manage association logos admin',
        ];

        $superAdminOnlyPermissions = [
            'view super dashboard',
            'view associations',
            'create associations',
            'update associations',
            'delete associations',
            'activate associations',
            'deactivate associations',
            'view users',
            'create users',
            'update users',
            'activate users',
            'deactivate users',
            'view roles',
            'view permissions',
            'manage role permissions',
            'manage settings',
            'view languages',
            'create languages',
            'update languages',
            'delete languages',
            'view super timelines',
        ];

        $rolePermissions = [
            'member' => $memberPermissions,
            'association_officer' => $associationOfficerPermissions,
            'institution_user' => $institutionUserPermissions,
            'admin' => $adminPermissions,
            'super_admin' => [
                ...$memberPermissions,
                ...$associationOfficerPermissions,
                ...$institutionUserPermissions,
                ...$adminPermissions,
                ...$superAdminOnlyPermissions,
            ],
        ];

        $permissions = collect($rolePermissions)
            ->flatten()
            ->unique()
            ->values()
            ->all();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach ($rolePermissions as $roleName => $permissionsForRole) {
            $role = Role::findOrCreate($roleName, 'web');

            $role->syncPermissions(array_values(array_unique($permissionsForRole)));
        }
    }
}
