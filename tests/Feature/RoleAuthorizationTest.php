<?php

namespace Tests\Feature;

use App\Models\User;
use App\Policies\RolePolicy;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_superadmin_has_unrestricted_access_without_assigned_permissions(): void
    {
        $superadminRole = Role::findOrCreate('superadmin', 'web');
        $superadmin = User::factory()->create();
        $superadmin->assignRole($superadminRole);

        $this->assertTrue(Gate::forUser($superadmin)->allows('Delete:Role'));
        $this->assertTrue(Gate::forUser($superadmin)->allows('unrestricted-superadmin-access'));

        $this->actingAs($superadmin);

        $this->assertTrue(RoleResource::canDeleteAny());
    }

    public function test_admin_cannot_delete_admin_or_superadmin_roles(): void
    {
        $deletePermission = Permission::findOrCreate('Delete:Role', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $superadminRole = Role::findOrCreate('superadmin', 'web');
        $superAdminRole = Role::findOrCreate('super_admin', 'web');
        $ordinaryRole = Role::findOrCreate('event encoder', 'web');

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);
        $admin->givePermissionTo($deletePermission);

        $policy = app(RolePolicy::class);

        $this->assertFalse($policy->delete($admin, $adminRole));
        $this->assertFalse($policy->delete($admin, $superadminRole));
        $this->assertFalse($policy->delete($admin, $superAdminRole));
        $this->assertTrue($policy->delete($admin, $ordinaryRole));
        $this->assertFalse($policy->deleteAny($admin));

        $this->actingAs($admin);

        $this->assertFalse(RoleResource::canDelete($superadminRole));
        $this->assertFalse(RoleResource::canDeleteAny());
    }

    public function test_admin_without_delete_role_permission_cannot_delete_roles_individually_or_in_bulk(): void
    {
        $adminRole = Role::findOrCreate('admin', 'web');
        $superadminRole = Role::findOrCreate('superadmin', 'web');
        $ordinaryRole = Role::findOrCreate('event encoder', 'web');

        $admin = User::factory()->create();
        $admin->assignRole($adminRole);

        $policy = app(RolePolicy::class);

        $this->assertFalse($policy->delete($admin, $superadminRole));
        $this->assertFalse($policy->delete($admin, $ordinaryRole));
        $this->assertFalse($policy->deleteAny($admin));

        $this->actingAs($admin);

        $this->assertFalse(RoleResource::canDelete($superadminRole));
        $this->assertFalse(RoleResource::canDelete($ordinaryRole));
        $this->assertFalse(RoleResource::canDeleteAny());
    }

    public function test_superadmin_with_permission_retains_role_deletion_authority(): void
    {
        $deletePermission = Permission::findOrCreate('Delete:Role', 'web');
        $adminRole = Role::findOrCreate('admin', 'web');
        $superadminRole = Role::findOrCreate('superadmin', 'web');

        $superadmin = User::factory()->create();
        $superadmin->assignRole($superadminRole);
        $superadmin->givePermissionTo($deletePermission);

        $policy = app(RolePolicy::class);

        $this->assertTrue($policy->delete($superadmin, $adminRole));
        $this->assertTrue($policy->delete($superadmin, $superadminRole));
        $this->assertTrue($policy->deleteAny($superadmin));
    }
}
