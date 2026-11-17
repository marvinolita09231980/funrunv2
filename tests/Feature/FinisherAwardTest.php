<?php

namespace Tests\Feature;

use App\Filament\Resources\Finishers\Pages\CreateFinisher;
use App\Models\Award;
use App\Models\Finisher;
use App\Models\Participant;
use App\Models\User;
use Filament\Actions\Action;
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

        $this->assertSame(
            4,
            Award::query()
                ->where('category', Award::CATEGORY_TOP_FINISHERS)
                ->count(),
        );
        $this->assertSame(
            8,
            Award::query()
                ->where('category', Award::CATEGORY_SPECIAL_AWARDS)
                ->count(),
        );
        $this->assertSame(
            6,
            Award::query()
                ->where('distance_category', Award::DISTANCE_5KM)
                ->count(),
        );
        $this->assertSame(
            6,
            Award::query()
                ->where('distance_category', Award::DISTANCE_10KM)
                ->count(),
        );
    }

    public function test_awards_are_grouped_by_category_for_finisher_recording(): void
    {
        $options = Award::groupedOptions();

        $this->assertSame([
            Award::CATEGORY_TOP_FINISHERS,
            Award::CATEGORY_SPECIAL_AWARDS,
        ], array_keys($options));
        $this->assertContains('5K Finisher Male', $options[Award::CATEGORY_TOP_FINISHERS]);
        $this->assertContains('Youngest Female 5K Winner', $options[Award::CATEGORY_SPECIAL_AWARDS]);

        $fiveKilometerOptions = collect(Award::groupedOptions(
            distanceCategory: '5km',
        ))->flatten()->all();

        $this->assertContains('5K Finisher Male', $fiveKilometerOptions);
        $this->assertNotContains('10K Finisher Male', $fiveKilometerOptions);
    }

    public function test_race_bib_prefixes_follow_the_registered_distance(): void
    {
        $this->assertSame('3', Finisher::raceBibPrefixForDistance('3km'));
        $this->assertSame('5', Finisher::raceBibPrefixForDistance('5Km'));
        $this->assertSame('1', Finisher::raceBibPrefixForDistance('10K'));
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
            'category' => Award::CATEGORY_TOP_FINISHERS,
            'distance_category' => Award::DISTANCE_5KM,
            'is_active' => true,
        ]);
        $wrongDistanceAward = Award::query()->create([
            'name' => '10Km Champion',
            'category' => Award::CATEGORY_TOP_FINISHERS,
            'distance_category' => Award::DISTANCE_10KM,
            'is_active' => true,
        ]);
        $participant = $this->participant();

        $component = Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['last_name' => 'Runner'])
            ->assertTableActionExists(
                'recordRunner',
                fn (Action $action): bool => ! $action->isModalClosedByClickingAway(),
                $participant,
            )
            ->mountTableAction('recordRunner', $participant);

        $component->assertTableActionDataSet([
            'racebib' => '5',
        ]);

        $component
            ->setTableActionData([
                'racebib' => '5001',
                'award_id' => $award->getKey(),
                'finish_time' => '01:02:03',
            ])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['racebib']);

        $component
            ->setTableActionData([
                'racebib' => '10001',
                'award_id' => $award->getKey(),
                'finish_time' => '01:02:03',
            ])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['racebib']);

        $component
            ->setTableActionData([
                'racebib' => '50001',
                'award_id' => $wrongDistanceAward->getKey(),
                'finish_time' => '01:02:03',
            ])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['award_id']);

        $this->assertDatabaseCount('finishers', 0);

        $component
            ->setTableActionData([
                'racebib' => '50001',
                'award_id' => $award->getKey(),
                'finish_time' => '01:02:03',
            ])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $finisher = Finisher::query()->sole();

        $this->assertSame($award->getKey(), $finisher->award_id);
        $this->assertTrue($finisher->award->is($award));
        $this->assertSame('Champion', $finisher->award->name);

        $secondParticipant = $this->participant([
            'participantNumber' => '2',
            'firstName' => 'Second',
            'lastName' => 'Runner',
        ]);

        $transferComponent = Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['last_name' => 'Runner'])
            ->mountTableAction('recordRunner', $secondParticipant)
            ->setTableActionData([
                'racebib' => '50003',
                'award_id' => $award->getKey(),
                'finish_time' => '01:03:00',
            ])
            ->assertSet('mountedActions.0.data.award_id', $award->getKey())
            ->assertSet('mountedActions.0.data.reassign_award', false)
            ->assertNotified('Award has a current winner')
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['award_id']);

        $this->assertDatabaseCount('finishers', 1);
        $this->assertSame($finisher->getKey(), Finisher::awardHolder($award->getKey())?->getKey());

        $transferComponent
            ->set('mountedActions.0.data.reassign_award', true)
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseCount('finishers', 2);
        $this->assertNull($finisher->refresh()->award_id);
        $this->assertSame(
            $secondParticipant->getKey(),
            Finisher::awardHolder($award->getKey())?->participants_id,
        );
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
            'racebib' => '50002',
            'finish_time' => now(),
            'award_id' => $award->getKey(),
            'created_by' => 'test-recorder',
        ]);

        $this->assertTrue($award->finishers->contains($finisher));
        $this->assertSame('Top Finisher', $finisher->award->name);
    }

    private function participant(array $attributes = []): Participant
    {
        return Participant::query()->create(array_merge([
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
        ], $attributes));
    }
}
