<?php

namespace Tests\Feature;

use App\Filament\Pages\RegistrationControl;
use App\Filament\Participant\Pages\RegisterForm;
use App\Models\RegistrationSetting;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegistrationControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_registration_is_active_by_default(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('register'));

        Livewire::test(RegisterForm::class)
            ->assertSet('registrationEnabled', true)
            ->assertSee('Personal Information')
            ->assertDontSee('Registration Temporarily Closed');
    }

    public function test_admin_can_deactivate_and_activate_public_registration(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(RegistrationControl::class)
            ->assertSet('registrationEnabled', true)
            ->call('toggleRegistration')
            ->assertSet('registrationEnabled', false)
            ->assertSee('Inactive')
            ->call('toggleRegistration')
            ->assertSet('registrationEnabled', true)
            ->assertSee('Active');

        $this->assertTrue(RegistrationSetting::kalamboanRegistrationEnabled());
    }

    public function test_public_link_uses_the_current_server_instead_of_the_configured_app_url(): void
    {
        config()->set('app.url', 'https://configured-host.example');

        $admin = $this->userWithRole('admin');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(RegistrationControl::class)
            ->assertSee('Official Online Registration')
            ->assertSee('Open Online Registration')
            ->assertSee('Copy Registration Link')
            ->assertSee('Share on Messenger')
            ->assertSee('https://www.messenger.com/')
            ->assertSee('navigator.share')
            ->assertDontSee('fb-messenger://')
            ->assertDontSee('Quick guide')
            ->assertDontSee('How it works')
            ->assertSee('http://localhost/kalamboan/funrun-register')
            ->assertDontSee('configured-host.example');
    }

    public function test_only_admin_and_superadmin_can_access_the_control(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $admin = $this->userWithRole('admin');
        $superadmin = $this->userWithRole('superadmin');
        $officer = $this->userWithRole('administrative officer');

        $this->actingAs($admin);
        $this->assertTrue(RegistrationControl::canAccess());

        $this->actingAs($superadmin);
        $this->assertTrue(RegistrationControl::canAccess());

        $this->actingAs($officer);
        $this->assertFalse(RegistrationControl::canAccess());
    }

    public function test_non_admin_cannot_call_the_toggle_operation(): void
    {
        $officer = $this->userWithRole('administrative officer');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($officer);

        Livewire::test(RegistrationControl::class)
            ->assertForbidden();

        $this->assertTrue(RegistrationSetting::kalamboanRegistrationEnabled());
    }

    public function test_deactivation_hides_and_blocks_the_registration_form(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('register'));
        RegistrationSetting::setKalamboanRegistrationEnabled(false);

        Livewire::test(RegisterForm::class)
            ->assertSet('registrationEnabled', false)
            ->assertSee('Registration Temporarily Closed')
            ->assertDontSee('Personal Information')
            ->call('create');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_an_open_form_cannot_submit_after_registration_is_deactivated(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('register'));

        $component = Livewire::test(RegisterForm::class)
            ->assertSet('registrationEnabled', true);

        RegistrationSetting::setKalamboanRegistrationEnabled(false);

        $component
            ->call('create')
            ->assertSet('registrationEnabled', false)
            ->assertSet('errorMessage', 'Public registration is currently closed.')
            ->assertSee('Registration Temporarily Closed');

        $this->assertDatabaseCount('participants', 0);
    }

    private function userWithRole(string $roleName): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::query()->firstOrCreate([
            'name' => $roleName,
            'guard_name' => 'web',
        ]));

        return $user;
    }
}
