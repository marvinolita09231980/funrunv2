<?php

namespace Tests\Feature;

use App\Filament\Resources\Finishers\FinisherResource;
use App\Filament\Resources\Finishers\Pages\CreateFinisher;
use App\Models\User;
use App\Policies\FinisherPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinisherAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_finisher_resource_is_hidden_and_forbidden_without_view_any_permission(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'finisher-unchecked',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo(Permission::findOrCreate('access_admin_panel'));
        $user = $this->createUser('Unchecked User', 'unchecked-user');
        $user->assignRole($role);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertFalse(FinisherResource::canAccess());
        $this->assertFalse(CreateFinisher::canAccess());
    }

    public function test_finisher_permissions_control_each_resource_action(): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'finisher-viewer',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo([
            Permission::findOrCreate('access_admin_panel'),
            Permission::findOrCreate('ViewAny:Finisher'),
        ]);
        $user = $this->createUser('Finisher Viewer', 'finisher-viewer');
        $user->assignRole($role);

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($user);

        $policy = new FinisherPolicy();

        $this->assertTrue($user->can('access_admin_panel'));
        $this->assertTrue($user->canAccessPanel(Filament::getPanel('admin')));
        $this->assertTrue(FinisherResource::canAccess());
        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->create($user));
        $this->assertFalse(CreateFinisher::canAccess());

        $role->givePermissionTo(Permission::findOrCreate('Create:Finisher'));

        $this->assertTrue(CreateFinisher::canAccess());
    }

    private function createUser(string $name, string $username): User
    {
        return User::query()->create([
            'name' => $name,
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => 'password',
        ]);
    }
}
