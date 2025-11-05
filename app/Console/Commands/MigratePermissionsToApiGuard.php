<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MigratePermissionsToApiGuard extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:migrate-to-api-guard';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate permissions and roles from web guard to api guard';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Migrating permissions and roles to API guard...');

        // Reset cached permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get all permissions with web guard
        $webPermissions = Permission::where('guard_name', 'web')->get();
        $this->info("Found {$webPermissions->count()} permissions with 'web' guard");

        // Create corresponding permissions with api guard
        $createdCount = 0;
        foreach ($webPermissions as $permission) {
            $existing = Permission::where('name', $permission->name)
                ->where('guard_name', 'api')
                ->first();

            if (!$existing) {
                Permission::create([
                    'name' => $permission->name,
                    'guard_name' => 'api',
                ]);
                $createdCount++;
            }
        }

        $this->info("Created {$createdCount} new permissions with 'api' guard");

        // Get all roles with web guard
        $webRoles = Role::where('guard_name', 'web')->get();
        $this->info("Found {$webRoles->count()} roles with 'web' guard");

        // Create corresponding roles with api guard and sync permissions
        foreach ($webRoles as $role) {
            $apiRole = Role::firstOrCreate(
                ['name' => $role->name, 'guard_name' => 'api'],
                ['guard_name' => 'api']
            );

            // Get permissions for this role (web guard)
            $rolePermissions = $role->permissions->pluck('name')->toArray();

            // Sync permissions using api guard
            if (!empty($rolePermissions)) {
                $apiPermissions = Permission::whereIn('name', $rolePermissions)
                    ->where('guard_name', 'api')
                    ->get();

                $apiRole->syncPermissions($apiPermissions);
                $this->info("Synced permissions for role '{$role->name}'");
            }
        }

        $this->info('✅ Migration completed successfully!');
        $this->warn('⚠️  Note: You may need to reassign roles to users manually if they were assigned with web guard.');

        return Command::SUCCESS;
    }
}

