<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const DISALLOWED_PERMISSIONS = [
        'ForceDelete:Participant',
        'ForceDeleteAny:Participant',
        'Reorder:Participant',
        'Replicate:Participant',
        'Restore:Participant',
        'RestoreAny:Participant',
    ];

    public function up(): void
    {
        $roleId = DB::table('roles')
            ->where('name', 'focal user')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::DISALLOWED_PERMISSIONS)
            ->pluck('id');

        DB::table('role_has_permissions')
            ->where('role_id', $roleId)
            ->whereIn('permission_id', $permissionIds)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $roleId = DB::table('roles')
            ->where('name', 'focal user')
            ->where('guard_name', 'web')
            ->value('id');

        if (! $roleId) {
            return;
        }

        $permissionIds = DB::table('permissions')
            ->where('guard_name', 'web')
            ->whereIn('name', self::DISALLOWED_PERMISSIONS)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_has_permissions')->insertOrIgnore([
                'permission_id' => $permissionId,
                'role_id' => $roleId,
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
