<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'access_admin_panel',
        'ViewAny:Participant',
        'View:Participant',
        'Create:Participant',
        'Update:Participant',
        'Delete:Participant',
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')
            ->where('name', 'focal user')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $roleId) {
            $roleId = DB::table('roles')->insertGetId([
                'name' => 'focal user',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::PERMISSIONS as $permissionName) {
            $permissionId = DB::table('permissions')
                ->where('name', $permissionName)
                ->where('guard_name', 'web')
                ->value('id');

            if (! $permissionId) {
                $permissionId = DB::table('permissions')->insertGetId([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')
            ->where('name', 'focal user')
            ->where('guard_name', 'web')
            ->value('id');

        if ($roleId) {
            $permissionIds = DB::table('permissions')
                ->where('guard_name', 'web')
                ->whereIn('name', self::PERMISSIONS)
                ->pluck('id');

            DB::table('role_has_permissions')
                ->where('role_id', $roleId)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
