<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // User permissions
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Role permissions
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            // Permission permissions
            'permissions.view',
            'permissions.create',
            'permissions.update',
            'permissions.delete',

            // Audit log permissions
            'audit-logs.view',

            // API key permissions
            'api-keys.view',
            'api-keys.create',
            'api-keys.update',
            'api-keys.delete',

            // Service Request permissions
            'service-requests.create',
            'service-requests.view',
            'service-requests.update',
            'service-requests.cancel',
            'service-requests.complete',
            'service-requests.respond',

            // Professional permissions
            'professionals.create',
            'professionals.update',
            'professionals.view',
            'professionals.delete',
            'professionals.manage',

            // Credit permissions
            'credits.view',
            'credits.deduct',
            'credits.transfer',
            'credits.add',
            'credits.manage-rules',

            // Review permissions
            'reviews.create',
            'reviews.view',
            'reviews.update',
            'reviews.delete',
            'reviews.moderate',

            // Chat permissions
            'chat.send',
            'chat.view',
            'chat.delete',

            // CMS permissions
            'cms.view',
            'cms.create',
            'cms.update',
            'cms.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission, 'guard_name' => 'api'],
                ['guard_name' => 'api']
            );
        }

        // Create roles
        $admin = Role::firstOrCreate(
            ['name' => 'admin', 'guard_name' => 'api'],
            ['guard_name' => 'api']
        );
        $moderator = Role::firstOrCreate(
            ['name' => 'moderator', 'guard_name' => 'api'],
            ['guard_name' => 'api']
        );
        $contractor = Role::firstOrCreate(
            ['name' => 'contractor', 'guard_name' => 'api'],
            ['guard_name' => 'api']
        );
        $professional = Role::firstOrCreate(
            ['name' => 'professional', 'guard_name' => 'api'],
            ['guard_name' => 'api']
        );
        $company = Role::firstOrCreate(
            ['name' => 'company', 'guard_name' => 'api'],
            ['guard_name' => 'api']
        );

        // Assign permissions to roles
        $admin->syncPermissions(Permission::where('guard_name', 'api')->get());

        $moderator->syncPermissions(
            Permission::whereIn('name', [
                'users.view',
                'users.update',
                'roles.view',
                'permissions.view',
                'audit-logs.view',
                'service-requests.view',
                'reviews.moderate',
                'professionals.view',
                'chat.view',
            ])->where('guard_name', 'api')->get()
        );

        $contractor->syncPermissions(
            Permission::whereIn('name', [
                'users.view',
                'service-requests.create',
                'service-requests.view',
                'service-requests.update',
                'service-requests.cancel',
                'service-requests.complete',
                'professionals.create',
                'credits.view',
                'credits.deduct',
                'credits.transfer',
                'reviews.create',
                'reviews.view',
                'chat.send',
                'chat.view',
                'cms.view',
            ])->where('guard_name', 'api')->get()
        );

        $professional->syncPermissions(
            Permission::whereIn('name', [
                'users.view',
                'users.update',
                'service-requests.view',
                'service-requests.respond',
                'professionals.create',
                'professionals.update',
                'professionals.view',
                'credits.view',
                'reviews.view',
                'chat.send',
                'chat.view',
                'cms.view',
            ])->where('guard_name', 'api')->get()
        );

        $company->syncPermissions(
            Permission::whereIn('name', [
                'users.view',
                'users.update',
                'service-requests.view',
                'service-requests.create',
                'professionals.create',
                'professionals.manage',
                'professionals.view',
                'credits.view',
                'credits.deduct',
                'credits.transfer',
                'reviews.view',
                'chat.send',
                'chat.view',
                'cms.view',
            ])->where('guard_name', 'api')->get()
        );
    }
}
