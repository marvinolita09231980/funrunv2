<?php

namespace Tests\Feature;

use App\Filament\Resources\Finishers\Pages\CreateFinisher;
use App\Models\Award;
use App\Models\Finisher;
use App\Models\Participant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class FinisherAwardTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_award_reference_values_are_seeded(): void
    {
        $this->assertSame([
            '10K Finisher Female',
            '10K Finisher Male',
            '5K Finisher Female',
            '5K Finisher Male',
            'Oldest Female 10K Winner',
            'Oldest Female 5K Winner',
            'Oldest Male 10K Winner',
            'Oldest Male 5K Winner',
            'Youngest Female 10K Winner',
            'Youngest Female 5K Winner',
            'Youngest Male 10K Winner',
            'Youngest Male 5K Winner',
        ], Award::query()->orderBy('name')->pluck('name')->all());
    }

    public function test_active_award_can_be_selected_when_recording_a_finisher(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->firstOrCreate([
            'name' => 'finisher-award-recorder',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo([
            Permission::findOrCreate('access_admin_panel'),
            Permission::findOrCreate('ViewAny:Finisher'),
            Permission::findOrCreate('Create:Finisher'),
        ]);

        $user = User::query()->create([
            'name' => 'Award Recorder',
            'username' => 'award-recorder',
            'email' => 'award-recorder@example.test',
            'password' => 'password',
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $award = Award::query()->create([
            'name' => 'Champion',
            'is_active' => true,
        ]);
        $participant = $this->participant();

        Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['last_name' => 'Runner'])
            ->mountTableAction('recordRunner', $participant)
            ->setTableActionData([
                'racebib' => '5001',
                'award_id' => $award->getKey(),
                'finish_time' => '01:02:03',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $finisher = Finisher::query()->sole();

        $this->assertSame($award->getKey(), $finisher->award_id);
        $this->assertTrue($finisher->award->is($award));
        $this->assertSame('Champion', $finisher->award->name);
    }

    public function test_used_award_retains_its_finisher_relationship(): void
    {
        $award = Award::query()->create([
            'name' => 'Top Finisher',
            'is_active' => true,
        ]);
        $participant = $this->participant();
        $finisher = Finisher::query()->create([
            'participants_id' => $participant->getKey(),
            'firstName' => $participant->firstName,
            'lastName' => $participant->lastName,
            'categoryDescription' => $participant->categoryDescription,
            'subDescription' => $participant->subDescription,
            'gender' => $participant->gender,
            'birthDate' => $participant->birthDate,
            'distanceCategory' => $participant->distanceCategory,
            'racebib' => '5002',
            'finish_time' => now(),
            'award_id' => $award->getKey(),
            'created_by' => 'test-recorder',
        ]);

        $this->assertTrue($award->finishers->contains($finisher));
        $this->assertSame('Top Finisher', $finisher->award->name);
    }

    private function participant(): Participant
    {
        return Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => '1',
            'firstName' => 'Awarded',
            'lastName' => 'Runner',
            'middleInitial' => null,
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
        ]);
    }
}
