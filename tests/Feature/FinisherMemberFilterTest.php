<?php

namespace Tests\Feature;

use App\Filament\Resources\Finishers\Pages\ListFinishers;
use App\Models\Finisher;
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

class FinisherMemberFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_finisher_filters_are_not_exposed_in_the_url(): void
    {
        $property = new ReflectionProperty(ListFinishers::class, 'tableFilters');

        $this->assertSame([], $property->getAttributes(Url::class));
    }

    public function test_finisher_list_filters_padac_and_rpwuds_runners_from_participant_data(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->firstOrCreate([
            'name' => 'finisher-group-viewer',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo([
            Permission::findOrCreate('access_admin_panel'),
            Permission::findOrCreate('ViewAny:Finisher'),
        ]);

        $user = User::query()->create([
            'name' => 'Finisher Group Viewer',
            'username' => 'finisher-group-viewer',
            'email' => 'finisher-group-viewer@example.test',
            'password' => 'password',
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $padac = $this->finisherFor([
            'firstName' => 'Padac',
            'categoryDescription' => 'PADAC MEMBERS',
        ], '50001');
        $legacyPadac = $this->finisherFor([
            'firstName' => 'Pdea',
            'categoryDescription' => 'ONA',
            'subDescription' => 'PDEA',
        ], '50002');
        $rpwuds = $this->finisherFor([
            'firstName' => 'Rpwuds',
            'rpwuds' => true,
        ], '50003');
        $both = $this->finisherFor([
            'firstName' => 'Both',
            'categoryDescription' => 'PADAC',
            'rpwuds' => true,
        ], '50004');
        $regular = $this->finisherFor([
            'firstName' => 'Regular',
        ], '50005');

        $component = Livewire::test(ListFinishers::class)
            ->assertTableFilterExists('runner_group')
            ->assertSee('Runner Group')
            ->assertSee('PADAC Member')
            ->assertSee('RPWUDS')
            ->assertTableColumnExists(
                'mobile_finisher_summary',
                fn ($column): bool => $column->getHiddenFrom() === 'md',
            )
            ->assertTableColumnExists(
                'desktop_finisher_name',
                fn ($column): bool => $column->getVisibleFrom() === 'md',
            )
            ->assertTableColumnExists(
                'desktop_category',
                fn ($column): bool => $column->getVisibleFrom() === 'md',
            )
            ->assertTableColumnDoesNotExist('desktop_year')
            ->filterTable('runner_group', 'padac')
            ->assertCanSeeTableRecords([$padac, $legacyPadac, $both])
            ->assertCanNotSeeTableRecords([$rpwuds, $regular]);

        $this->assertNull($component->instance()->getTable()->getContentGrid());
        $this->assertFalse($component->instance()->getTable()->hasColumnsLayout());
        $this->assertTrue($component->instance()->getTable()->isStriped());
        $this->assertSame('Recorded Finishers', $component->instance()->getTable()->getHeading());
        $this->assertSame(25, $component->instance()->getTable()->getDefaultPaginationPageOption());

        $component
            ->filterTable('runner_group', 'rpwuds')
            ->assertCanSeeTableRecords([$rpwuds, $both])
            ->assertCanNotSeeTableRecords([$padac, $legacyPadac, $regular]);
    }

    private function finisherFor(array $attributes, string $raceBib): Finisher
    {
        $participant = Participant::query()->create(array_merge([
            'year' => now()->year,
            'participantNumber' => $raceBib,
            'firstName' => 'Sample',
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
        ], $attributes));

        return Finisher::query()->create([
            'participants_id' => $participant->getKey(),
            'firstName' => $participant->firstName,
            'lastName' => $participant->lastName,
            'categoryDescription' => $participant->categoryDescription,
            'subDescription' => $participant->subDescription,
            'gender' => $participant->gender,
            'birthDate' => $participant->birthDate,
            'distanceCategory' => $participant->distanceCategory,
            'racebib' => $raceBib,
            'finish_time' => now(),
            'created_by' => 'test-recorder',
        ]);
    }
}
