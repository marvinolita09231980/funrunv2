<?php

namespace Tests\Feature;

use App\Filament\Resources\Finishers\Pages\CreateFinisher;
use App\Filament\Resources\Finishers\Pages\EditFinisher;
use App\Models\Award;
use App\Models\Finisher;
use App\Models\Participant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
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
            ->filterTable('runner_name', ['participant_id' => $participant->getKey()])
            ->assertTableColumnExists(
                'mobile_runner_summary',
                fn ($column): bool => $column->getHiddenFrom() === 'md',
            )
            ->assertTableColumnExists(
                'desktop_runner_name',
                fn ($column): bool => $column->getVisibleFrom() === 'md',
            )
            ->assertTableColumnExists(
                'desktop_birth_date',
                fn ($column): bool => $column->getVisibleFrom() === 'md',
            )
            ->assertTableColumnExists(
                'desktop_distance',
                fn ($column): bool => $column->getVisibleFrom() === 'md',
            )
            ->assertTableColumnExists(
                'desktop_category',
                fn ($column): bool => $column->getVisibleFrom() === 'md',
            )
            ->assertTableColumnExists(
                'desktop_organization',
                fn ($column): bool => $column->getVisibleFrom() === 'md',
            )
            ->assertTableActionExists(
                'recordRunner',
                fn (Action $action): bool => ! $action->isModalClosedByClickingAway()
                    && $action->getModalWidth() === '3xl',
                $participant,
            )
            ->mountTableAction('recordRunner', $participant);

        $this->assertNull($component->instance()->getTable()->getContentGrid());
        $this->assertFalse($component->instance()->getTable()->hasColumnsLayout());
        $this->assertTrue($component->instance()->getTable()->isStriped());

        $runnerLookup = $component->instance()
            ->getTable()
            ->getFilter('runner_name')
            ->getSchema()
            ->getComponent('participant_id');
        $this->assertInstanceOf(Select::class, $runnerLookup);
        $this->assertSame(150, $runnerLookup->getSearchDebounce());
        $this->assertSame([], $runnerLookup->getSearchResults(''));
        $this->assertSame([
            $participant->getKey() => 'Runner, Awarded - 01/01/1990',
        ], $runnerLookup->getSearchResults('Runner'));
        $this->assertSame([
            $participant->getKey() => 'Runner, Awarded - 01/01/1990',
        ], $runnerLookup->getSearchResults('Award'));

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

        Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['participant_id' => $secondParticipant->getKey()])
            ->mountTableAction('recordRunner', $secondParticipant)
            ->setTableActionData([
                'racebib' => '50001',
                'award_id' => null,
                'finish_time' => '01:03:00',
            ])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['racebib' => 'unique']);

        $this->assertDatabaseCount('finishers', 1);

        $transferComponent = Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['participant_id' => $secondParticipant->getKey()])
            ->mountTableAction('recordRunner', $secondParticipant)
            ->setTableActionData([
                'racebib' => '50003',
                'award_id' => $award->getKey(),
                'finish_time' => '01:03:00',
            ])
            ->assertSet('mountedActions.0.data.award_id', $award->getKey())
            ->assertSet('mountedActions.0.data.reassign_award', false);

        $transferComponent
            ->set('mountedActions.0.data.award_id', null)
            ->assertSet('mountedActions.0.data.reassign_award', false)
            ->set('mountedActions.0.data.award_id', $wrongDistanceAward->getKey())
            ->assertSet('mountedActions.0.data.reassign_award', false)
            ->set('mountedActions.0.data.award_id', $award->getKey())
            ->assertSet('mountedActions.0.data.reassign_award', false)
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['award_id']);

        $notice = view('filament.resources.finishers.partials.award-conflict-notice', [
            'get' => fn (string $field): mixed => $field === 'award_id' ? $award->getKey() : null,
            'record' => $secondParticipant,
        ])->render();

        $this->assertStringContainsString('Award has a current winner', $notice);
        $this->assertStringContainsString('Awarded Runner already received this award', $notice);
        $this->assertStringContainsString('(recorded by award-recorder).', $notice);
        $this->assertStringContainsString('class="award-conflict-notice"', $notice);
        $this->assertStringContainsString('color: var(--warning-950)', $notice);
        $this->assertStringContainsString('persistent', $notice);

        foreach ([null, $wrongDistanceAward->getKey()] as $availableAwardId) {
            $noticeWithoutHolder = view('filament.resources.finishers.partials.award-conflict-notice', [
                'get' => fn (string $field): mixed => $field === 'award_id' ? $availableAwardId : null,
                'record' => $secondParticipant,
            ])->render();

            $this->assertStringNotContainsString('Award has a current winner', $noticeWithoutHolder);
        }

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

    public function test_existing_finisher_can_win_one_main_and_one_special_award_only(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->firstOrCreate([
            'name' => 'multiple-award-recorder',
            'guard_name' => 'web',
        ]);
        $role->givePermissionTo([
            Permission::findOrCreate('access_admin_panel'),
            Permission::findOrCreate('ViewAny:Finisher'),
            Permission::findOrCreate('Create:Finisher'),
        ]);

        $user = User::query()->create([
            'name' => 'Multiple Award Recorder',
            'username' => 'multiple-award-recorder',
            'email' => 'multiple-award-recorder@example.test',
            'password' => 'password',
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $participant = $this->participant();
        $mainAward = Award::query()->where('name', '5K Finisher Male')->firstOrFail();
        $secondMainAward = Award::query()->create([
            'name' => '5K Second Place Male',
            'category' => Award::CATEGORY_TOP_FINISHERS,
            'distance_category' => Award::DISTANCE_5KM,
            'is_active' => true,
        ]);
        $oldestAward = Award::query()->where('name', 'Oldest Male 5K Winner')->firstOrFail();
        $youngestAward = Award::query()->where('name', 'Youngest Male 5K Winner')->firstOrFail();
        $tenKilometerAward = Award::query()->where('name', 'Oldest Male 10K Winner')->firstOrFail();

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
            'award_id' => $mainAward->getKey(),
            'created_by' => $user->username,
        ]);

        Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['participant_id' => $participant->getKey()])
            ->mountTableAction('recordRunner', $participant)
            ->setTableActionData(['award_id' => $tenKilometerAward->getKey()])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['award_id']);

        Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['participant_id' => $participant->getKey()])
            ->assertCanSeeTableRecords([$participant])
            ->mountTableAction('recordRunner', $participant)
            ->setTableActionData(['award_id' => $oldestAward->getKey()])
            ->callMountedTableAction()
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseCount('finishers', 1);
        $this->assertEqualsCanonicalizing([
            $mainAward->getKey(),
            $oldestAward->getKey(),
        ], $finisher->refresh()->awards()->pluck('awards.id')->all());

        Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['participant_id' => $participant->getKey()])
            ->mountTableAction('recordRunner', $participant)
            ->setTableActionData(['award_id' => $secondMainAward->getKey()])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['award_id']);

        Livewire::test(CreateFinisher::class)
            ->filterTable('runner_name', ['participant_id' => $participant->getKey()])
            ->mountTableAction('recordRunner', $participant)
            ->setTableActionData(['award_id' => $youngestAward->getKey()])
            ->callMountedTableAction()
            ->assertHasTableActionErrors(['award_id']);

        $notice = view('filament.resources.finishers.partials.award-conflict-notice', [
            'get' => fn (string $field): mixed => $field === 'award_id' ? $youngestAward->getKey() : null,
            'record' => $participant,
        ])->render();

        $this->assertStringContainsString('Runner already has an award in this category', $notice);
        $this->assertStringContainsString('can receive only one award per category', $notice);
        $this->assertDatabaseCount('award_finisher', 2);
    }

    public function test_finisher_with_an_existing_award_can_open_the_edit_form(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $role = Role::query()->firstOrCreate([
            'name' => 'superadmin',
            'guard_name' => 'web',
        ]);
        $user = User::query()->create([
            'name' => 'Finisher Editor',
            'username' => 'finisher-editor',
            'email' => 'finisher-editor@example.test',
            'password' => 'password',
        ]);
        $user->assignRole($role);
        $this->actingAs($user);

        $award = Award::query()->create([
            'name' => 'Existing Inactive Award',
            'category' => Award::CATEGORY_SPECIAL_AWARDS,
            'distance_category' => Award::DISTANCE_5KM,
            'is_active' => false,
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
            'created_by' => $user->username,
        ]);

        $otherParticipant = $this->participant([
            'participantNumber' => '2',
            'firstName' => 'Other',
        ]);
        Finisher::query()->create([
            'participants_id' => $otherParticipant->getKey(),
            'firstName' => $otherParticipant->firstName,
            'lastName' => $otherParticipant->lastName,
            'categoryDescription' => $otherParticipant->categoryDescription,
            'subDescription' => $otherParticipant->subDescription,
            'gender' => $otherParticipant->gender,
            'birthDate' => $otherParticipant->birthDate,
            'distanceCategory' => $otherParticipant->distanceCategory,
            'racebib' => '50004',
            'finish_time' => now(),
            'created_by' => $user->username,
        ]);

        $component = Livewire::test(EditFinisher::class, [
            'record' => $finisher->getRouteKey(),
        ])
            ->assertFormFieldExists('award_id', fn ($field): bool =>
                $field->getColumnSpan('default') === 1
                && $field->getColumnSpan('md') === 2)
            ->assertSet('data.award_id', $award->getKey())
            ->assertDontSee('race bib: exactly 5 numbers starting with')
            ->assertSee('Existing Inactive Award');

        $component
            ->call('save')
            ->assertHasNoFormErrors()
            ->fillForm(['racebib' => '50004'])
            ->call('save')
            ->assertHasFormErrors(['racebib' => 'unique']);

        $this->assertSame('50002', $finisher->refresh()->racebib);
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
