<?php

namespace Tests\Feature;

use App\Livewire\ContactInformationPrompt;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ContactInformationPromptTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_missing_contact_details_can_dismiss_the_prompt(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'cellphone_number' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(ContactInformationPrompt::class)
            ->assertSet('shouldPrompt', true)
            ->assertSee('Complete Your Contact Information')
            ->assertSee('Not Now, Continue')
            ->call('openPrompt')
            ->assertDispatched('open-modal', id: 'contact-information-prompt')
            ->call('dismiss')
            ->assertSet('shouldPrompt', false);

        $this->assertTrue(session('contact_information_prompt_dismissed'));

        Livewire::test(ContactInformationPrompt::class)
            ->assertSet('shouldPrompt', false);
    }

    public function test_user_can_save_email_and_cellphone_for_account_recovery(): void
    {
        $user = User::factory()->create([
            'email' => null,
            'cellphone_number' => null,
        ]);

        $this->actingAs($user);

        Livewire::test(ContactInformationPrompt::class)
            ->set('email', 'Recovery.User@Example.test')
            ->set('cellphoneNumber', '09171234567')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('shouldPrompt', false);

        $user->refresh();

        $this->assertSame('recovery.user@example.test', $user->email);
        $this->assertSame('09171234567', $user->cellphone_number);
        $this->assertTrue(session('contact_information_prompt_dismissed'));
    }

    public function test_completed_contact_information_does_not_open_the_prompt(): void
    {
        $user = User::factory()->create([
            'email' => 'complete@example.test',
            'cellphone_number' => '09170000000',
        ]);

        $this->actingAs($user);

        Livewire::test(ContactInformationPrompt::class)
            ->assertSet('shouldPrompt', false)
            ->assertDontSee('Complete Your Contact Information');
    }

    public function test_admin_panel_has_email_only_password_recovery_enabled(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasPasswordReset());
        $this->assertNotNull($panel->getRequestPasswordResetUrl());
    }

    public function test_authenticated_panel_page_mounts_the_contact_prompt(): void
    {
        config()->set('app.env', 'local');

        $user = User::factory()->create([
            'email' => null,
            'cellphone_number' => null,
        ]);
        $user->assignRole(Role::query()->firstOrCreate([
            'name' => 'superadmin',
            'guard_name' => 'web',
        ]));
        $user->givePermissionTo(Permission::findOrCreate('access_admin_panel'));

        $this->actingAs($user)
            ->get('/admin/profile')
            ->assertOk()
            ->assertSee('Complete Your Contact Information')
            ->assertSee('contact-information-prompt');
    }
}
