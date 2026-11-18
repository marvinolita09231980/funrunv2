<?php

namespace Tests\Feature;

use App\Filament\Resources\Participants\Pages\ListParticipants;
use App\Models\Participant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Livewire\Attributes\Url;
use ReflectionProperty;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantRunnerGroupFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_participant_filters_are_not_exposed_in_the_url(): void
    {
        $property = new ReflectionProperty(ListParticipants::class, 'tableFilters');

        $this->assertSame([], $property->getAttributes(Url::class));
    }

    public function test_participants_can_be_filtered_by_padac_rpwuds_couple_or_family(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo([
            Permission::findOrCreate('access_admin_panel'),
            Permission::findOrCreate('ViewAny:Participant'),
        ]);

        $user = User::query()->create([
            'name' => 'Participant Group Viewer',
            'username' => 'participant-group-viewer',
            'email' => 'participant-group-viewer@example.test',
            'password' => 'password',
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $padac = $this->participant('Padac', [
            'categoryDescription' => 'PADAC MEMBERS',
        ]);
        $rpwuds = $this->participant('Rpwuds', [
            'rpwuds' => true,
        ]);
        $couple = $this->participant('Couple', [
            'couple' => true,
            'subDescription' => 'Couple',
            'participantNumber' => 'C-100',
        ]);
        $couplePartner = $this->participant('Partner', [
            'couple' => true,
            'subDescription' => 'Couple',
            'participantNumber' => 'C-100',
        ]);
        $legacyCouple = $this->participant('LegacyCouple', [
            'couple' => false,
            'subDescription' => 'Couple',
        ]);
        $family = $this->participant('Family', [
            'couple' => false,
            'subDescription' => 'Family',
            'participantNumber' => 'F-100',
        ]);
        $familyChild = $this->participant('Child', [
            'couple' => false,
            'subDescription' => 'Family',
            'participantNumber' => 'F-100',
        ]);
        $familySibling = $this->participant('Sibling', [
            'couple' => false,
            'subDescription' => 'Family',
            'participantNumber' => 'F-100',
        ]);
        $regular = $this->participant('Regular');

        $component = Livewire::test(ListParticipants::class)
            ->assertTableFilterExists('runner_group')
            ->assertSee('Runner Group')
            ->assertSee('PADAC Members')
            ->assertSee('RPWUDS')
            ->assertSee('Couple')
            ->assertSee('Family')
            ->filterTable('runner_group', 'padac')
            ->assertTableColumnHidden('relationship_group')
            ->assertTableColumnHidden('related_runners')
            ->assertCanSeeTableRecords([$padac])
            ->assertCanNotSeeTableRecords([$rpwuds, $couple, $couplePartner, $legacyCouple, $family, $familyChild, $familySibling, $regular]);

        $component
            ->filterTable('runner_group', 'rpwuds')
            ->assertCanSeeTableRecords([$rpwuds])
            ->assertCanNotSeeTableRecords([$padac, $couple, $couplePartner, $legacyCouple, $family, $familyChild, $familySibling, $regular]);

        $component
            ->filterTable('runner_group', 'couple')
            ->assertTableColumnVisible('relationship_group')
            ->assertTableColumnVisible('related_runners')
            ->assertTableColumnStateSet('relationship_group', '┌─ #C-100', $couple)
            ->assertTableColumnStateSet('relationship_group', '└─ #C-100', $couplePartner)
            ->assertTableColumnStateSet('related_runners', 'Partner Runner', $couple)
            ->assertTableColumnStateSet('related_runners', 'Couple Runner', $couplePartner)
            ->assertSeeHtml('data-group-bracket="top"')
            ->assertSeeHtml('data-group-bracket="bottom"')
            ->assertCanSeeTableRecords([$couple, $couplePartner, $legacyCouple], inOrder: true)
            ->assertCanNotSeeTableRecords([$padac, $rpwuds, $family, $familyChild, $familySibling, $regular]);

        $component
            ->filterTable('runner_group', 'family')
            ->assertTableColumnVisible('relationship_group')
            ->assertTableColumnVisible('related_runners')
            ->assertTableColumnStateSet('relationship_group', '┌─ #F-100', $family)
            ->assertTableColumnStateSet('relationship_group', '├─ #F-100', $familyChild)
            ->assertTableColumnStateSet('relationship_group', '└─ #F-100', $familySibling)
            ->assertTableColumnStateSet('related_runners', 'Child Runner, Sibling Runner', $family)
            ->assertTableColumnStateSet('related_runners', 'Family Runner, Sibling Runner', $familyChild)
            ->assertSeeHtml('data-group-bracket="middle"')
            ->assertCanSeeTableRecords([$family, $familyChild, $familySibling], inOrder: true)
            ->assertCanNotSeeTableRecords([$padac, $rpwuds, $couple, $couplePartner, $legacyCouple, $regular]);
    }

    private function participant(string $firstName, array $attributes = []): Participant
    {
        return Participant::query()->create(array_merge([
            'year' => now()->year,
            'participantNumber' => uniqid('runner-', true),
            'firstName' => $firstName,
            'lastName' => 'Runner',
            'categoryDescription' => 'OPEN CATEGORY',
            'subDescription' => 'Individual',
            'shirtSize' => 'medium',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Test address',
            'contactNumber' => '09123456789',
            'created_by' => 'opencategory',
            'rpwuds' => false,
            'couple' => false,
        ], $attributes));
    }
}
