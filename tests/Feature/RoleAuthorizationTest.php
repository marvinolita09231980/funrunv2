<?php

namespace Tests\Feature;

use App\Models\User;
use App\Policies\RolePolicy;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_be_authorized_by_filament_in_production(): void
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate('superadmin', 'web'));

        $this->assertInstanceOf(FilamentUser::class, $user);
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
    }

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

    public function test_superadmin_impersonation_can_enter_panel_as_a_lower_account_without_panel_permission(): void
    {
        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin', 'web'));

        $lowerAccount = User::factory()->create();
        $lowerAccount->assignRole(Role::findOrCreate('event encoder', 'web'));

        $panel = Filament::getPanel('admin');

        $this->actingAs($lowerAccount);
        $this->assertFalse($lowerAccount->canAccessPanel($panel));

        session()->put(
            config('laravel-impersonate.session_key', 'impersonated_by'),
            $superadmin->getKey(),
        );

        $this->assertTrue($lowerAccount->canAccessPanel($panel));

        session()->forget(config('laravel-impersonate.session_key', 'impersonated_by'));
        $this->assertFalse($lowerAccount->canAccessPanel($panel));
    }

    public function test_non_superadmin_impersonation_does_not_bypass_panel_access(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::findOrCreate('admin', 'web'));

        $lowerAccount = User::factory()->create();
        $lowerAccount->assignRole(Role::findOrCreate('event encoder', 'web'));

        $this->actingAs($lowerAccount);
        session()->put(
            config('laravel-impersonate.session_key', 'impersonated_by'),
            $admin->getKey(),
        );

        $this->assertFalse($lowerAccount->canAccessPanel(Filament::getPanel('admin')));
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
