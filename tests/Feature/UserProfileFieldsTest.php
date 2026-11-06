<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserProfileFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_profile_and_generated_username_are_stored(): void
    {
        $user = User::query()->create([
            'name' => 'Juan Dela Cruz',
            'office' => 'Provincial Administrator Office',
            'cellphone_number' => '09123456789',
            'email' => 'juan@example.test',
            'password' => 'password',
        ]);

        $this->assertSame('Juan Dela Cruz', $user->name);
        $this->assertSame('Provincial Administrator Office', $user->office);
        $this->assertSame('09123456789', $user->cellphone_number);
        $this->assertSame('juandelacruz', $user->username);
        $this->assertDatabaseHas('users', [
            'id' => $user->getKey(),
            'name' => 'Juan Dela Cruz',
            'office' => 'Provincial Administrator Office',
            'cellphone_number' => '09123456789',
            'username' => 'juandelacruz',
        ]);

        $user->update(['cellphone_number' => null]);

        $this->assertNull($user->fresh()->cellphone_number);
    }

    public function test_user_can_be_created_without_an_email_address(): void
    {
        $user = User::query()->create([
            'name' => 'No Email User',
            'office' => 'Administrative Office',
            'password' => 'password',
        ]);

        $this->assertNull($user->email);
        $this->assertSame('noemailuser', $user->username);
    }

    public function test_admin_cannot_view_manage_or_impersonate_a_superadmin(): void
    {
        $adminRole = Role::query()->create([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $superAdminRole = Role::query()->create([
            'name' => 'superadmin',
            'guard_name' => 'web',
        ]);

        $admin = User::factory()->create(['name' => 'Administrator']);
        $admin->assignRole($adminRole);
        $superAdmin = User::factory()->create(['name' => 'Protected Superadmin']);
        $superAdmin->assignRole($superAdminRole);
        $regularUser = User::factory()->create(['name' => 'Regular User']);

        foreach (['View:User', 'Update:User', 'Delete:User', 'ForceDelete:User', 'Restore:User', 'Replicate:User'] as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission));
        }

        $visibleUserIds = UserResource::applyVisibilityForUser(User::query(), $admin)
            ->pluck('id');

        $this->assertFalse($visibleUserIds->contains($superAdmin->getKey()));
        $this->assertTrue($visibleUserIds->contains($regularUser->getKey()));

        $policy = new UserPolicy();
        $this->assertFalse($policy->view($admin, $superAdmin));
        $this->assertFalse($policy->update($admin, $superAdmin));
        $this->assertFalse($policy->delete($admin, $superAdmin));
        $this->assertFalse($policy->forceDelete($admin, $superAdmin));
        $this->assertFalse($policy->restore($admin, $superAdmin));
        $this->assertFalse($policy->replicate($admin, $superAdmin));

        $this->actingAs($admin);
        $this->assertFalse($superAdmin->canBeImpersonated());
        $this->assertTrue($regularUser->canBeImpersonated());

        $visibleToSuperAdmin = UserResource::applyVisibilityForUser(
            User::query(),
            $superAdmin,
        )->pluck('id');

        $this->assertTrue($visibleToSuperAdmin->contains($superAdmin->getKey()));
    }
}
