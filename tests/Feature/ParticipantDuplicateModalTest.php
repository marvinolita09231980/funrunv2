<?php

namespace Tests\Feature;

use App\Filament\Resources\Participants\Pages\CreateParticipant;
use App\Models\Participant;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantDuplicateModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_year_duplicate_opens_modal_with_creator_contact_information(): void
    {
        $admin = $this->authorizedAdmin();
        $creator = $this->createUser(
            'Creator Officer',
            'creator-officer',
            '09171234567',
        );
        $creator->assignRole(Role::findByName('administrative officer'));

        $this->createSubcategory('CATEGORY A', 'Office A');
        $this->createSubcategory('CATEGORY B', 'Office B');
        $existing = $this->createParticipant($creator, 'CATEGORY A', 'Office A');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(CreateParticipant::class)
            ->fillForm([
                'firstName' => $existing->firstName,
                'lastName' => $existing->lastName,
                'birthDate' => $existing->birthDate,
                'gender' => 'male',
                'address' => 'Test Address',
                'contactNumber' => '09123456789',
                'pwd' => '0',
                'rpwuds' => '0',
                'categoryDescription' => 'CATEGORY B',
                'subDescription' => 'Office B',
                'shirtSize' => 'medium',
                'distanceCategory' => '5km',
            ])
            ->assertSet('duplicateParticipant.creator_name', 'Creator Officer')
            ->assertSet('duplicateParticipant.creator_role', 'Administrative Officer')
            ->assertSet('duplicateParticipant.creator_phone', '09171234567')
            ->assertSet('duplicateParticipant.category', 'CATEGORY A')
            ->assertSet('duplicateParticipant.subcategory', 'Office A')
            ->assertSee('Please contact')
            ->assertSee('09171234567')
            ->call('create')
            ->assertHasFormErrors(['firstName'])
            ->assertDispatched('open-modal', id: 'duplicate-participant')
            ->call('clearDuplicateParticipantForm')
            ->assertSet('duplicateParticipant', null)
            ->assertSet('data.firstName', null)
            ->assertSet('data.lastName', null)
            ->assertSet('data.birthDate', null)
            ->assertSet('data.categoryDescription', null)
            ->assertSet('data.subDescription', null)
            ->assertHasNoFormErrors()
            ->assertDispatched('close-modal', id: 'duplicate-participant');

        $this->assertDatabaseCount('participants', 1);
    }

    public function test_duplicate_modal_identifies_a_focal_user_creator(): void
    {
        $admin = $this->authorizedAdmin();
        $creator = $this->createUser('Focal Creator', 'focal-creator', '09998887777');
        $creator->assignRole(Role::findByName('focal user'));
        $participant = $this->createParticipant($creator, 'CATEGORY A', 'Office A');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(CreateParticipant::class)
            ->fillForm([
                'firstName' => $participant->firstName,
                'lastName' => $participant->lastName,
                'birthDate' => $participant->birthDate,
            ])
            ->assertSet('duplicateParticipant.creator_name', 'Focal Creator')
            ->assertSet('duplicateParticipant.creator_role', 'Focal User')
            ->assertSet('duplicateParticipant.creator_phone', '09998887777');
    }

    private function authorizedAdmin(): User
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo([
            Permission::findOrCreate('access_admin_panel'),
            Permission::findOrCreate('Create:Participant'),
            Permission::findOrCreate('ViewAny:Participant'),
        ]);

        $user = $this->createUser('Administrator', 'administrator');
        $user->assignRole($role);

        return $user;
    }

    private function createUser(
        string $name,
        string $username,
        ?string $cellphoneNumber = null,
    ): User {
        return User::query()->create([
            'name' => $name,
            'username' => $username,
            'office' => 'Test Office',
            'cellphone_number' => $cellphoneNumber,
            'email' => "{$username}@example.test",
            'password' => 'password',
        ]);
    }

    private function createSubcategory(string $category, string $subcategory): Subcategory
    {
        return Subcategory::query()->create([
            'categoryDescription' => $category,
            'subDescription' => $subcategory,
            'nop' => 100,
        ]);
    }

    private function createParticipant(
        User $creator,
        string $category,
        string $subcategory,
    ): Participant {
        return Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => uniqid('participant-', true),
            'firstName' => 'Existing',
            'lastName' => 'Runner',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'address' => 'Original Address',
            'contactNumber' => '09111111111',
            'categoryDescription' => $category,
            'subDescription' => $subcategory,
            'shirtSize' => 'medium',
            'distanceCategory' => '5km',
            'waiver' => true,
            'referenceNumber' => '0',
            'created_by' => $creator->username,
        ]);
    }
}
