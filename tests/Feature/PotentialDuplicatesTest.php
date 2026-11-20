<?php

namespace Tests\Feature;

use App\Exports\AttendanceSheetExport;
use App\Exports\FilteredSheetExport;
use App\Exports\FoodAttendanceSheetExport;
use App\Exports\InPlaceOfDuplicatesExport;
use App\Filament\Pages\PotentialDuplicates;
use App\Filament\Resources\Participants\Pages\ListParticipants;
use App\Filament\Resources\Participants\Tables\ParticipantsTable;
use App\Models\AuditLog;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Mockery;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Tests\TestCase;

class PotentialDuplicatesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_and_ao_can_access_but_focal_user_cannot(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $officer = $this->userWithRole(
            'Duplicate Officer',
            'duplicate-officer',
            'administrative officer',
        );
        $focal = $this->userWithRole('Duplicate Focal', 'duplicate-focal', 'focal user');

        $this->actingAs($admin);
        $this->assertTrue(PotentialDuplicates::canAccess());

        $this->actingAs($officer);
        $this->assertTrue(PotentialDuplicates::canAccess());

        $this->actingAs($focal);
        $this->assertFalse(PotentialDuplicates::canAccess());
    }

    public function test_page_displays_same_year_duplicates_across_categories_and_birthdates(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $year = now()->year;

        $firstDuplicate = $this->participant([
            'year' => $year,
            'firstName' => 'Maria Elena',
            'lastName' => 'Santos',
            'birthDate' => '1994-06-15',
            'contactNumber' => '09171111111',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'shirtSize' => 'xs',
            'distanceCategory' => '3km',
        ]);
        $secondDuplicate = $this->participant([
            'year' => $year,
            'firstName' => '  maria ',
            'lastName' => 'SANTOS ',
            'birthDate' => '1994-06-15',
            'categoryDescription' => 'CATEGORY B',
            'subDescription' => 'Office B',
            'shirtSize' => '2xl',
            'distanceCategory' => '10km',
        ]);
        $differentBirthDate = $this->participant([
            'year' => $year,
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'birthDate' => '1995-06-15',
            'categoryDescription' => 'CATEGORY C',
            'subDescription' => 'Office C',
        ]);
        $differentYear = $this->participant([
            'year' => $year - 1,
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'birthDate' => '1994-06-15',
            'categoryDescription' => 'CATEGORY D',
            'subDescription' => 'Office D',
        ]);
        $exactOnlyFirst = $this->participant([
            'year' => $year,
            'firstName' => 'Ana',
            'lastName' => 'Lopez',
            'birthDate' => '1993-03-03',
            'categoryDescription' => 'CATEGORY E',
        ]);
        $exactOnlySecond = $this->participant([
            'year' => $year,
            'firstName' => ' ANA ',
            'lastName' => 'LOPEZ',
            'birthDate' => '1993-03-03',
            'categoryDescription' => 'CATEGORY F',
        ]);

        $this->actingAs($admin);

        $component = Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertCanSeeTableRecords([
                $firstDuplicate,
                $secondDuplicate,
                $differentBirthDate,
                $exactOnlyFirst,
                $exactOnlySecond,
            ])
            ->assertCanNotSeeTableRecords([$differentYear])
            ->assertTableColumnStateSet('potential_duplicate_count', 1, $firstDuplicate)
            ->assertTableColumnStateSet('potential_duplicate_count', 2, $secondDuplicate)
            ->assertTableColumnStateSet('potential_duplicate_count', 1, $differentBirthDate)
            ->assertTableColumnStateSet('potential_duplicate_count', 1, $exactOnlyFirst)
            ->assertTableColumnStateSet('potential_duplicate_count', 1, $exactOnlySecond)
            ->assertTableColumnStateSet('duplicate_group', '┌─ Duplicate Group 1', $firstDuplicate)
            ->assertTableColumnStateSet('duplicate_group', '├─ Duplicate Group 1', $secondDuplicate)
            ->assertTableColumnStateSet('duplicate_group', '└─ Duplicate Group 1', $differentBirthDate)
            ->assertTableColumnStateSet('duplicate_group', '┌─ Duplicate Group 2', $exactOnlyFirst)
            ->assertTableColumnStateSet('duplicate_group', '└─ Duplicate Group 2', $exactOnlySecond)
            ->assertTableColumnStateSet('shirtSize', 'xs', $firstDuplicate)
            ->assertTableColumnStateSet('shirtSize', '2xl', $secondDuplicate)
            ->assertTableColumnStateSet('distanceCategory', '3km', $firstDuplicate)
            ->assertTableColumnStateSet('distanceCategory', '10km', $secondDuplicate)
            ->assertTableColumnExists(
                'shirtSize',
                fn ($column): bool => $column->getVisibleFrom() === null
                    && ! $column->isIndividuallySearchable(),
            )
            ->assertTableColumnExists(
                'distanceCategory',
                fn ($column): bool => $column->getVisibleFrom() === null
                    && ! $column->isIndividuallySearchable(),
            )
            ->assertSee('Phone 09171111111')
            ->assertSee('Shirt Size')
            ->assertSee('XS')
            ->assertSee('2XL')
            ->assertSee('Race Distance')
            ->assertSee('Rave Run (3K)')
            ->assertSee('10KM')
            ->assertTableFilterExists('shirt_size')
            ->assertTableFilterExists('race_distance')
            ->assertSee('e.g. XS, M, or 2XL')
            ->assertSee('e.g. Rave Run (3K), 5KM, or 10KM')
            ->assertSee('Reset')
            ->assertSee('High-Probability Groups')
            ->assertSee('Records for Review')
            ->assertSee('High Probability')
            ->assertSee('CATEGORY A')
            ->assertSee('CATEGORY B')
            ->assertSee('Relationship brackets connect records that belong to the same potential duplicate group.')
            ->assertSeeHtml('data-duplicate-bracket="top"')
            ->assertSeeHtml('data-duplicate-bracket="bottom"')
            ->assertTableActionExists(
                'review',
                fn (Action $action): bool => $action->getModalWidth() === '7xl'
                    && ! $action->isModalClosedByClickingAway(),
                $firstDuplicate,
            );

        $table = $component->instance()->getTable();
        $this->assertNull($table->getContentGrid());
        $this->assertNull($table->getGrouping());
        $this->assertSame(FiltersLayout::AboveContent, $table->getFiltersLayout());
        $this->assertTrue($table->persistsFiltersInSession());
        $this->assertSame([], collect($table->getColumns())
            ->filter(fn ($column): bool => $column->isIndividuallySearchable())
            ->keys()
            ->values()
            ->all());

        $reviewAction = $table->getAction('review')->getClone()->record($firstDuplicate);
        $comparisonHtml = $reviewAction->getModalContent()->render();

        $this->assertTrue($reviewAction->shouldOpenModal());
        $this->assertSame('Compare Duplicate Group 1', $reviewAction->getModalHeading());
        $this->assertStringContainsString('Santos, Maria Elena', $comparisonHtml);
        $this->assertStringContainsString('data-comparison-participant="'.$firstDuplicate->getKey().'"', $comparisonHtml);
        $this->assertStringContainsString('data-comparison-participant="'.$secondDuplicate->getKey().'"', $comparisonHtml);
        $this->assertStringContainsString('data-comparison-participant="'.$differentBirthDate->getKey().'"', $comparisonHtml);
        $this->assertStringContainsString('June 15, 1994', $comparisonHtml);
        $this->assertStringContainsString('June 15, 1995', $comparisonHtml);
        $this->assertStringContainsString('Shirt Size', $comparisonHtml);
        $this->assertStringContainsString('XS', $comparisonHtml);
        $this->assertStringContainsString('2XL', $comparisonHtml);
        $this->assertStringContainsString('Choose This Record to Update', $comparisonHtml);
        $this->assertStringContainsString('Available to update', $comparisonHtml);
        $this->assertStringContainsString('data-replace-duplicate="'.$differentBirthDate->getKey().'"', $comparisonHtml);
        $this->assertStringContainsString('data-replace-duplicate="'.$firstDuplicate->getKey().'"', $comparisonHtml);
        $this->assertStringContainsString('data-replace-duplicate="'.$secondDuplicate->getKey().'"', $comparisonHtml);
        $this->assertStringContainsString('data-duplicate-comparison', $comparisonHtml);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr))', $comparisonHtml);

        $replacementSelection = Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->mountTableAction('review', $firstDuplicate)
            ->call('mountAction', 'replaceDuplicate', [
                'participant' => (string) $differentBirthDate->getKey(),
            ]);

        $this->assertSame(
            'replaceDuplicate',
            $replacementSelection->instance()->getMountedTableAction()?->getName(),
        );
        $this->assertTrue(
            $replacementSelection->instance()->getMountedTableAction()?->getRecord()?->is($differentBirthDate),
        );
        $replacementSelection->assertTableActionDataSet([
                'originalFirstName' => 'Maria',
                'firstName' => null,
                'originalLastName' => 'Santos',
                'lastName' => null,
                'categoryDescription' => PotentialDuplicates::REPLACEMENT_CATEGORY,
                'shirtSize' => 'medium',
                'distanceCategory' => '5km',
            ]);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->searchTable('Rave Run (3K)')
            ->assertCanSeeTableRecords([$firstDuplicate])
            ->assertCanNotSeeTableRecords([
                $secondDuplicate,
                $differentBirthDate,
                $exactOnlyFirst,
                $exactOnlySecond,
            ]);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->searchTable('10KM')
            ->assertCanSeeTableRecords([$secondDuplicate])
            ->assertCanNotSeeTableRecords([$firstDuplicate]);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->searchTable('XS')
            ->assertCanSeeTableRecords([$firstDuplicate])
            ->assertCanNotSeeTableRecords([$secondDuplicate]);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->searchTable('2XL')
            ->assertCanSeeTableRecords([$secondDuplicate])
            ->assertCanNotSeeTableRecords([$firstDuplicate]);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->searchTable('Maria XS 3K')
            ->assertCanSeeTableRecords([$firstDuplicate])
            ->assertCanNotSeeTableRecords([$secondDuplicate]);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->searchTable('Santos 2XL 10KM')
            ->assertCanSeeTableRecords([$secondDuplicate])
            ->assertCanNotSeeTableRecords([$firstDuplicate]);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->filterTable('shirt_size', ['value' => 'XS'])
            ->filterTable('race_distance', ['value' => 'Rave Run (3K)'])
            ->assertCanSeeTableRecords([$firstDuplicate])
            ->assertCanNotSeeTableRecords([$secondDuplicate]);

        Livewire::test(PotentialDuplicates::class)
            ->assertSet('tableFilters.shirt_size.value', 'XS')
            ->assertSet('tableFilters.race_distance.value', 'Rave Run (3K)')
            ->resetTableFilters()
            ->assertSet('tableFilters.shirt_size.value', null)
            ->assertSet('tableFilters.race_distance.value', null);
    }

    public function test_page_detects_shortened_initialed_and_phonetically_misspelled_first_names(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $year = now()->year;

        $markJohn = $this->participant([
            'year' => $year,
            'firstName' => 'Mark John',
            'lastName' => 'Cruz',
            'birthDate' => '1992-04-10',
            'categoryDescription' => 'CATEGORY A',
        ]);
        $mark = $this->participant([
            'year' => $year,
            'firstName' => 'Mark',
            'lastName' => 'Cruz',
            'birthDate' => '1992-04-10',
            'categoryDescription' => 'CATEGORY B',
        ]);
        $jason = $this->participant([
            'year' => $year,
            'firstName' => 'Jason',
            'lastName' => 'Reyes',
            'birthDate' => '1988-08-08',
            'categoryDescription' => 'CATEGORY C',
        ]);
        $jasonWithInitial = $this->participant([
            'year' => $year,
            'firstName' => 'Jason L.',
            'lastName' => 'Reyes',
            'birthDate' => '1988-08-08',
            'categoryDescription' => 'CATEGORY D',
        ]);
        $jayson = $this->participant([
            'year' => $year,
            'firstName' => 'Jayson',
            'lastName' => 'Garcia',
            'birthDate' => '1991-09-09',
            'categoryDescription' => 'CATEGORY E',
        ]);
        $jasonMisspelledMatch = $this->participant([
            'year' => $year,
            'firstName' => 'Jason',
            'lastName' => 'Garcia',
            'birthDate' => '1991-09-09',
            'categoryDescription' => 'CATEGORY F',
        ]);
        $differentFirstName = $this->participant([
            'year' => $year,
            'firstName' => 'Michael',
            'lastName' => 'Cruz',
            'birthDate' => '1992-04-10',
            'categoryDescription' => 'CATEGORY G',
        ]);

        $this->actingAs($admin);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertCanSeeTableRecords([
                $markJohn,
                $mark,
                $jason,
                $jasonWithInitial,
                $jayson,
                $jasonMisspelledMatch,
            ])
            ->assertCanNotSeeTableRecords([$differentFirstName])
            ->assertSee('shortened names')
            ->assertSee('small phonetic spelling differences');
    }

    public function test_only_superadmin_or_assigned_ao_can_delete_from_potential_duplicates(): void
    {
        $superadmin = $this->userWithRole(
            'Duplicate Superadmin',
            'duplicate-superadmin',
            'superadmin',
        );
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $officer = $this->userWithRole(
            'Duplicate Officer',
            'duplicate-officer',
            'administrative officer',
        );
        $focal = $this->userWithRole('Duplicate Focal', 'duplicate-focal', 'focal user');

        $categoryAAllocation = CategoryAllocation::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'allocated_participants' => 10,
        ]);
        $officer->administeredCategoryAllocations()->attach($categoryAAllocation);

        $categoryARecord = $this->participant([
            'firstName' => 'Mark John',
            'lastName' => 'Cruz',
            'birthDate' => '1992-04-10',
            'categoryDescription' => 'CATEGORY A',
        ]);
        $categoryBRecord = $this->participant([
            'firstName' => 'Mark',
            'lastName' => 'Cruz',
            'birthDate' => '1992-04-10',
            'categoryDescription' => 'CATEGORY B',
        ]);

        $this->assertTrue(PotentialDuplicates::canDeletePotentialDuplicate(
            $superadmin,
            $categoryARecord,
        ));
        $this->assertTrue(PotentialDuplicates::canDeletePotentialDuplicate(
            $superadmin,
            $categoryBRecord,
        ));
        $this->assertFalse(PotentialDuplicates::canDeletePotentialDuplicate(
            $admin,
            $categoryARecord,
        ));
        $this->assertTrue(PotentialDuplicates::canDeletePotentialDuplicate(
            $officer,
            $categoryARecord,
        ));
        $this->assertFalse(PotentialDuplicates::canDeletePotentialDuplicate(
            $officer,
            $categoryBRecord,
        ));
        $this->assertFalse(PotentialDuplicates::canDeletePotentialDuplicate(
            $focal,
            $categoryARecord,
        ));

        $this->actingAs($superadmin);
        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertTableActionVisible('delete', $categoryARecord)
            ->assertTableActionVisible('delete', $categoryBRecord);

        $this->actingAs($admin);
        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertTableActionHidden('delete', $categoryARecord)
            ->assertTableActionHidden('delete', $categoryBRecord);

        $this->actingAs($officer);
        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertTableActionVisible('delete', $categoryARecord)
            ->assertTableActionHidden('delete', $categoryBRecord)
            ->callTableAction('delete', $categoryARecord);

        $this->assertDatabaseMissing('participants', [
            'id' => $categoryARecord->getKey(),
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $categoryBRecord->getKey(),
        ]);

        $this->actingAs($focal);
        $this->assertFalse(PotentialDuplicates::canAccess());
    }

    public function test_admin_or_assigned_ao_can_replace_one_duplicate_with_a_different_participant(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $officer = $this->userWithRole(
            'Duplicate Officer',
            'duplicate-officer',
            'administrative officer',
        );
        $allocation = CategoryAllocation::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'allocated_participants' => 0,
        ]);
        $allocation->administrativeOfficers()->attach($officer);

        $original = $this->participant([
            'firstName' => 'ALFRED JOHN',
            'lastName' => 'MILAR',
            'birthDate' => '1999-01-09',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'shirtSize' => 'medium',
            'distanceCategory' => '5km',
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
        $recordToReplace = $this->participant([
            'firstName' => 'Alfred John',
            'lastName' => 'Milar',
            'birthDate' => '1999-01-09',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'shirtSize' => 'large',
            'distanceCategory' => '10km',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $unassignedCategoryRecord = new Participant([
            'categoryDescription' => 'CATEGORY B',
        ]);

        $this->assertTrue(PotentialDuplicates::canReplacePotentialDuplicate($admin, $recordToReplace));
        $this->assertTrue(PotentialDuplicates::canReplacePotentialDuplicate($officer, $recordToReplace));
        $this->assertTrue(PotentialDuplicates::canReplacePotentialDuplicate($admin, $original));
        $this->assertTrue(PotentialDuplicates::canReplacePotentialDuplicate($officer, $original));
        $this->assertFalse(PotentialDuplicates::canReplacePotentialDuplicate($officer, $unassignedCategoryRecord));

        $this->actingAs($admin);
        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertTableActionHidden('replaceDuplicate', $recordToReplace);

        $this->actingAs($officer);
        $replacementComponent = Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertTableActionHidden('replaceDuplicate', $recordToReplace);
        $replacementComponent->instance()->replacePotentialDuplicateParticipant(
            $recordToReplace,
            [
                'firstName' => 'Brenda Mae',
                'lastName' => 'Cruz',
                'middleInitial' => 'Q',
                'birthDate' => '2001-04-12',
                'gender' => 'female',
                'address' => 'Replacement participant address',
                'contactNumber' => '09998887777',
                'pwd' => true,
                'rpwuds' => false,
            ],
        );
        $replacementComponent->assertCountTableRecords(0);

        $this->assertDatabaseCount('participants', 2);
        $this->assertDatabaseHas('participants', [
            'id' => $original->getKey(),
            'firstName' => 'ALFRED JOHN',
            'lastName' => 'MILAR',
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $recordToReplace->getKey(),
            'firstName' => 'Brenda Mae',
            'lastName' => 'Cruz',
            'middleInitial' => 'Q',
            'birthDate' => '2001-04-12',
            'categoryDescription' => PotentialDuplicates::REPLACEMENT_CATEGORY,
            'subDescription' => '',
            'shirtSize' => 'large',
            'distanceCategory' => '10km',
            'address' => 'Replacement participant address',
            'created_by' => $officer->username,
        ]);

        $replacementLog = AuditLog::query()
            ->where('event', 'replaced')
            ->where('auditable_type', Participant::class)
            ->where('auditable_id', (string) $recordToReplace->getKey())
            ->sole();

        $this->assertSame('Alfred John', $replacementLog->old_values['firstName']);
        $this->assertSame('Brenda Mae', $replacementLog->new_values['firstName']);
        $this->assertSame('Milar', $replacementLog->old_values['lastName']);
        $this->assertSame('Cruz', $replacementLog->new_values['lastName']);
        $this->assertSame('large', $replacementLog->old_values['shirtSize']);
        $this->assertSame('large', $replacementLog->new_values['shirtSize']);
        $this->assertSame('10km', $replacementLog->old_values['distanceCategory']);
        $this->assertSame('10km', $replacementLog->new_values['distanceCategory']);
        $this->assertSame('CATEGORY A', $replacementLog->old_values['categoryDescription']);
        $this->assertSame('duplicate-admin', $replacementLog->old_values['created_by']);
        $this->assertSame($officer->username, $replacementLog->new_values['created_by']);
        $this->assertSame(
            PotentialDuplicates::REPLACEMENT_CATEGORY,
            $replacementLog->new_values['categoryDescription'],
        );
        $this->assertSame('Duplicate Officer', $replacementLog->actor_name);
        $this->assertDatabaseCount('audit_logs', 1);
        $this->assertDatabaseMissing('category_allocations', [
            'categoryDescription' => PotentialDuplicates::REPLACEMENT_CATEGORY,
        ]);

        $comparison = (new InPlaceOfDuplicatesExport(now()->year))
            ->comparisonRows()
            ->sole();
        $this->assertSame('Alfred John', $comparison['original']['First Name']);
        $this->assertSame('Brenda Mae', $comparison['replacement']['First Name']);
        $this->assertSame('CATEGORY A', $comparison['original']['Category']);
        $this->assertSame(
            PotentialDuplicates::REPLACEMENT_CATEGORY,
            $comparison['replacement']['Category'],
        );
        $this->assertSame('duplicate-admin', $comparison['original']['Registered By']);
        $this->assertSame($officer->username, $comparison['replacement']['Registered By']);
        $this->assertSame('Duplicate Officer', $comparison['details']['Replaced By']);
        $this->assertNotEmpty(Excel::raw(
            new InPlaceOfDuplicatesExport(now()->year),
            \Maatwebsite\Excel\Excel::XLSX,
        ));

        Excel::fake();
        $response = $replacementComponent->instance()->downloadReplacementHistory();
        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        Excel::assertDownloaded(
            'in-place-of-duplicates-'.now()->year.'.xlsx',
            fn (InPlaceOfDuplicatesExport $export): bool => $export->comparisonRows()->count() === 1,
        );

        $recordToRestore = $recordToReplace->fresh();
        $this->assertTrue(PotentialDuplicates::canRestoreReplacedParticipant(
            $admin,
            $recordToRestore,
        ));
        $this->assertTrue(PotentialDuplicates::canRestoreReplacedParticipant(
            $officer,
            $recordToRestore,
        ));

        Livewire::test(ListParticipants::class)
            ->assertTableActionVisible('restoreReplacement', $recordToRestore)
            ->callTableAction('restoreReplacement', $recordToRestore)
            ->assertNotified('Original runner restored');

        $this->assertDatabaseHas('participants', [
            'id' => $recordToReplace->getKey(),
            'firstName' => 'Alfred John',
            'lastName' => 'Milar',
            'birthDate' => '1999-01-09',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'shirtSize' => 'large',
            'distanceCategory' => '10km',
            'created_by' => 'duplicate-admin',
        ]);
        $restorationLog = AuditLog::query()
            ->where('event', 'restored')
            ->where('auditable_id', (string) $recordToReplace->getKey())
            ->sole();
        $this->assertSame('Brenda Mae', $restorationLog->old_values['firstName']);
        $this->assertSame('Alfred John', $restorationLog->new_values['firstName']);
        $this->assertSame(
            PotentialDuplicates::REPLACEMENT_CATEGORY,
            $restorationLog->old_values['categoryDescription'],
        );
        $this->assertSame('CATEGORY A', $restorationLog->new_values['categoryDescription']);
        $this->assertSame('Duplicate Officer', $restorationLog->actor_name);
        $this->assertDatabaseCount('audit_logs', 2);
    }

    public function test_page_shows_generation_status_before_loading_duplicate_results(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');

        $this->actingAs($admin);

        Livewire::test(PotentialDuplicates::class)
            ->assertSet('duplicatesReady', false)
            ->assertSee('Generating potential duplicates')
            ->assertSee('Please wait while we carefully compare participant records.')
            ->call('loadPotentialDuplicates')
            ->assertSet('duplicatesReady', true)
            ->assertDontSee('Generating potential duplicates')
            ->assertSee('High-Probability Duplicate Review');
    }

    public function test_replacement_rejects_a_runner_who_is_already_registered(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $original = $this->participant([
            'firstName' => 'Duplicate Person',
            'lastName' => 'Runner',
            'birthDate' => '1990-01-01',
        ]);
        $recordToReplace = $this->participant([
            'firstName' => 'DUPLICATE PERSON',
            'lastName' => 'RUNNER',
            'birthDate' => '1990-01-01',
        ]);
        $alreadyRegistered = $this->participant([
            'firstName' => 'Already     Registered',
            'lastName' => 'Participant',
            'birthDate' => '1995-05-15',
        ]);

        $this->actingAs($admin);
        $reactiveComponent = Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->mountTableAction('review', $original)
            ->call('mountAction', 'replaceDuplicate', [
                'participant' => (string) $recordToReplace->getKey(),
            ])
            ->set('mountedActions.1.data.firstName', ' already registered ')
            ->set('mountedActions.1.data.lastName', 'PARTICIPANT')
            ->set('mountedActions.1.data.birthDate', '1995-05-15')
            ->assertHasErrors([
                'mountedActions.1.data.firstName',
                'mountedActions.1.data.lastName',
                'mountedActions.1.data.birthDate',
            ])
            ->assertNotified('Runner already registered');

        $this->assertTrue($reactiveComponent->instance()->replacementRunnerAlreadyRegistered);
        $this->assertTrue(
            $reactiveComponent->instance()->getMountedAction()?->getModalSubmitAction()?->isDisabled(),
        );
        $this->assertSame(
            'replaceDuplicate',
            $reactiveComponent->instance()->getMountedAction()?->getName(),
        );

        $reactiveComponent
            ->set('mountedActions.1.data.firstName', 'Unique Replacement')
            ->assertSet('replacementRunnerAlreadyRegistered', false)
            ->assertHasNoErrors([
                'mountedActions.1.data.firstName',
                'mountedActions.1.data.lastName',
                'mountedActions.1.data.birthDate',
            ]);
        $this->assertFalse(
            $reactiveComponent->instance()->getMountedAction()?->getModalSubmitAction()?->isDisabled(),
        );

        $component = Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates');

        try {
            $component->instance()->replacePotentialDuplicateParticipant(
                $recordToReplace,
                [
                    'firstName' => ' already registered ',
                    'lastName' => 'PARTICIPANT',
                    'birthDate' => '1995-05-15',
                    'gender' => 'female',
                    'address' => 'Replacement address',
                    'contactNumber' => '09998887777',
                    'pwd' => false,
                    'rpwuds' => false,
                ],
            );

            $this->fail('The already registered runner should have been rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                "This runner is already registered for {$recordToReplace->year} and cannot be used as a replacement.",
                $exception->errors()['firstName'][0],
            );
            $this->assertArrayHasKey('lastName', $exception->errors());
            $this->assertArrayHasKey('birthDate', $exception->errors());
        }

        $component->assertNotified('Runner already registered');

        $this->assertDatabaseHas('participants', [
            'id' => $recordToReplace->getKey(),
            'firstName' => 'DUPLICATE PERSON',
            'lastName' => 'RUNNER',
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $alreadyRegistered->getKey(),
            'firstName' => 'Already     Registered',
        ]);
        $this->assertDatabaseHas('participants', [
            'id' => $original->getKey(),
            'firstName' => 'Duplicate Person',
        ]);
        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_generation_does_not_run_correlated_duplicate_self_joins(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');

        $this->participant([
            'firstName' => 'Maria Elena',
            'lastName' => 'Santos',
            'birthDate' => '1994-06-15',
        ]);
        $this->participant([
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'birthDate' => '1994-06-15',
        ]);

        $queries = [];
        DB::listen(function (QueryExecuted $query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->actingAs($admin);

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates');

        $this->assertFalse(collect($queries)->contains(
            fn (string $query): bool => str_contains($query, 'potential_duplicate.')
                || str_contains($query, 'matching_participant.'),
        ));
    }

    public function test_authorized_user_can_download_printable_duplicate_report(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $year = now()->year;

        $this->participant([
            'year' => $year,
            'firstName' => 'Maria Elena',
            'lastName' => 'Santos',
            'birthDate' => '1994-06-15',
            'address' => 'Poblacion, Nabunturan',
            'contactNumber' => '09171234567',
        ]);
        $this->participant([
            'year' => $year,
            'firstName' => 'Maria',
            'lastName' => 'Santos',
            'birthDate' => '1994-06-15',
            'address' => 'Mankilam, Tagum City',
            'contactNumber' => '09981234567',
        ]);

        $pdfDocument = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $pdfDocument->shouldReceive('setPaper')
            ->once()
            ->with('a4', 'landscape')
            ->andReturnSelf();
        $pdfDocument->shouldReceive('output')->once()->andReturn('%PDF duplicate report');

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use ($year): bool {
                if ($view !== 'pdf.potential-duplicates-report') {
                    return false;
                }

                $html = view($view, $data)->render();

                return $data['report']['year'] === $year
                    && $data['report']['record_count'] === 2
                    && $data['report']['group_count'] === 1
                    && str_contains($html, 'Name')
                    && str_contains($html, 'Age')
                    && str_contains($html, 'Address')
                    && str_contains($html, 'Phone Number')
                    && str_contains($html, 'Maria Elena')
                    && str_contains($html, 'Poblacion, Nabunturan')
                    && str_contains($html, '09171234567');
            })
            ->andReturn($pdfDocument);

        $this->actingAs($admin);

        $component = Livewire::test(PotentialDuplicates::class)
            ->assertActionExists('downloadPdfReport')
            ->assertActionExists('downloadReplacementHistory');
        $response = $component->instance()->downloadPdfReport();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString(
            "potential-duplicates-{$year}.pdf",
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_food_and_singlet_attendance_exports_keep_one_eligible_record_per_duplicate_group(): void
    {
        $outsideSelection = $this->participant([
            'firstName' => 'Scoped',
            'lastName' => 'Runner',
            'birthDate' => '1980-01-01',
            'categoryDescription' => 'OUTSIDE CATEGORY',
            'subDescription' => 'Outside Office',
        ]);
        $onlyEligibleMatch = $this->participant([
            'firstName' => ' SCOPED ',
            'lastName' => 'RUNNER',
            'birthDate' => '1990-01-01',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
        ]);
        $keptDuplicate = $this->participant([
            'firstName' => 'Duplicate',
            'lastName' => 'Participant',
            'birthDate' => '1991-02-02',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
        ]);
        $excludedDuplicate = $this->participant([
            'firstName' => 'DUPLICATE',
            'lastName' => 'PARTICIPANT',
            'birthDate' => '1998-08-08',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
        ]);
        $uniqueParticipant = $this->participant([
            'firstName' => 'Unique',
            'lastName' => 'Entrant',
            'birthDate' => '1993-03-03',
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
        ]);
        $selection = [[
            'category' => 'CATEGORY A',
            'subcategory' => 'Office A',
        ]];

        foreach ([
            new AttendanceSheetExport($selection, 'A', 'Z'),
            new FoodAttendanceSheetExport($selection, 'A', 'Z'),
        ] as $export) {
            $exportedIds = $export->participantsForSheet()->pluck('id')->all();

            $this->assertEqualsCanonicalizing([
                $onlyEligibleMatch->getKey(),
                $keptDuplicate->getKey(),
                $uniqueParticipant->getKey(),
            ], $exportedIds);
            $this->assertNotContains($outsideSelection->getKey(), $exportedIds);
            $this->assertNotContains($excludedDuplicate->getKey(), $exportedIds);
        }

        foreach ([
            new AttendanceSheetExport($selection, 'A', 'Z', excludeDuplicates: false),
            new FoodAttendanceSheetExport($selection, 'A', 'Z', excludeDuplicates: false),
        ] as $export) {
            $exportedIds = $export->participantsForSheet()->pluck('id')->all();

            $this->assertEqualsCanonicalizing([
                $onlyEligibleMatch->getKey(),
                $keptDuplicate->getKey(),
                $excludedDuplicate->getKey(),
                $uniqueParticipant->getKey(),
            ], $exportedIds);
            $this->assertNotContains($outsideSelection->getKey(), $exportedIds);
        }

        $filteredData = [
            'year' => now()->year,
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
        ];
        $deduplicatedFilteredIds = (new FilteredSheetExport([
            ...$filteredData,
            'excludeDuplicates' => true,
        ]))->participantsForSheet()->pluck('id')->all();
        $completeFilteredIds = (new FilteredSheetExport([
            ...$filteredData,
            'excludeDuplicates' => false,
        ]))->participantsForSheet()->pluck('id')->all();

        $this->assertEqualsCanonicalizing([
            $onlyEligibleMatch->getKey(),
            $keptDuplicate->getKey(),
            $uniqueParticipant->getKey(),
        ], $deduplicatedFilteredIds);
        $this->assertEqualsCanonicalizing([
            $onlyEligibleMatch->getKey(),
            $keptDuplicate->getKey(),
            $excludedDuplicate->getKey(),
            $uniqueParticipant->getKey(),
        ], $completeFilteredIds);
    }

    public function test_replacement_category_is_available_in_attendance_singlet_and_filtered_exports(): void
    {
        $admin = $this->userWithRole('Duplicate Admin', 'duplicate-admin', 'admin');
        $officer = $this->userWithRole(
            'Duplicate Officer',
            'duplicate-officer',
            'administrative officer',
        );
        $replacement = $this->participant([
            'firstName' => 'Replacement',
            'lastName' => 'Runner',
            'categoryDescription' => PotentialDuplicates::REPLACEMENT_CATEGORY,
            'subDescription' => '',
        ]);
        $otherParticipant = $this->participant([
            'firstName' => 'Other',
            'lastName' => 'Runner',
        ]);

        foreach ([$admin, $officer] as $user) {
            $this->assertSame(
                PotentialDuplicates::REPLACEMENT_CATEGORY,
                ParticipantsTable::attendanceSubcategoryOptions($user)[ParticipantsTable::REPLACEMENT_CATEGORY_OPTION],
            );

            $selected = ParticipantsTable::exportSubcategoriesForUser(
                $user,
                [ParticipantsTable::REPLACEMENT_CATEGORY_OPTION],
            );

            $this->assertCount(1, $selected);
            $this->assertSame(
                PotentialDuplicates::REPLACEMENT_CATEGORY,
                $selected->first()->categoryDescription,
            );
        }

        $this->assertArrayHasKey(
            PotentialDuplicates::REPLACEMENT_CATEGORY,
            ParticipantsTable::filteredExportDescriptionOptions(),
        );

        $selection = [[
            'category' => PotentialDuplicates::REPLACEMENT_CATEGORY,
            'subcategory' => '',
        ]];

        foreach ([
            new AttendanceSheetExport($selection, 'A', 'Z', excludeDuplicates: false),
            new FoodAttendanceSheetExport($selection, 'A', 'Z', excludeDuplicates: false),
        ] as $export) {
            $this->assertSame(
                [$replacement->getKey()],
                $export->participantsForSheet()->pluck('id')->all(),
            );
            $this->assertNotContains(
                $otherParticipant->getKey(),
                $export->participantsForSheet()->pluck('id')->all(),
            );
        }

        $this->assertSame(
            [$replacement->getKey()],
            (new FilteredSheetExport([
                'year' => now()->year,
                'categoryDescription' => PotentialDuplicates::REPLACEMENT_CATEGORY,
                'letterStart' => 'A',
                'letterEnd' => 'Z',
                'excludeDuplicates' => false,
            ]))->participantsForSheet()->pluck('id')->all(),
        );
    }

    public function test_attendance_distance_and_filtered_letter_range_filters_are_applied(): void
    {
        $threeKilometerRunner = $this->participant([
            'firstName' => 'Aaron',
            'lastName' => 'Alpha',
            'distanceCategory' => '3km',
        ]);
        $fiveKilometerRunner = $this->participant([
            'firstName' => 'Bella',
            'lastName' => 'Miller',
            'distanceCategory' => '5km',
        ]);
        $tenKilometerRunner = $this->participant([
            'firstName' => 'Carlo',
            'lastName' => 'Zulu',
            'distanceCategory' => '10km',
        ]);
        $outsideCategoryRunner = $this->participant([
            'firstName' => 'Nora',
            'lastName' => 'Newton',
            'distanceCategory' => '10km',
            'categoryDescription' => 'CATEGORY B',
            'subDescription' => 'Office B',
        ]);
        $selection = [[
            'category' => 'CATEGORY A',
            'subcategory' => 'Office A',
        ]];

        foreach ([
            new AttendanceSheetExport($selection, 'A', 'Z', distanceCategory: '5km'),
            new FoodAttendanceSheetExport($selection, 'A', 'Z', distanceCategory: '5km'),
        ] as $export) {
            $this->assertSame(
                [$fiveKilometerRunner->getKey()],
                $export->participantsForSheet()->pluck('id')->all(),
            );
        }

        foreach ([
            new AttendanceSheetExport($selection, 'B', 'Y', distanceCategory: 'all'),
            new FoodAttendanceSheetExport($selection, 'B', 'Y', distanceCategory: 'all'),
        ] as $export) {
            $this->assertSame(
                [$fiveKilometerRunner->getKey()],
                $export->participantsForSheet()->pluck('id')->all(),
            );
        }

        $letterRangeIds = (new FilteredSheetExport([
            'year' => now()->year,
            'distanceCategory' => 'all',
            'categoryDescription' => 'CATEGORY A',
            'letterStart' => 'B',
            'letterEnd' => 'Y',
            'excludeDuplicates' => false,
        ]))->participantsForSheet()->pluck('id')->all();

        $this->assertSame([$fiveKilometerRunner->getKey()], $letterRangeIds);

        $reversedLetterRangeIds = (new FilteredSheetExport([
            'year' => now()->year,
            'distanceCategory' => 'all',
            'categoryDescription' => 'CATEGORY A',
            'letterStart' => 'Y',
            'letterEnd' => 'B',
            'excludeDuplicates' => false,
        ]))->participantsForSheet()->pluck('id')->all();

        $this->assertSame([$fiveKilometerRunner->getKey()], $reversedLetterRangeIds);
        $this->assertNotContains($threeKilometerRunner->getKey(), $letterRangeIds);
        $this->assertNotContains($tenKilometerRunner->getKey(), $letterRangeIds);
        $this->assertNotContains($outsideCategoryRunner->getKey(), $letterRangeIds);
    }


    private function userWithRole(string $name, string $username, string $roleName): User
    {
        $user = User::query()->create([
            'name' => $name,
            'username' => $username,
            'email' => "{$username}@example.test",
            'password' => 'password',
        ]);
        $user->assignRole(Role::findOrCreate($roleName, 'web'));

        return $user;
    }

    private function participant(array $overrides): Participant
    {
        return Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => uniqid('participant-', true),
            'firstName' => 'Participant',
            'lastName' => 'Runner',
            'middleInitial' => null,
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'shirtSize' => 'medium',
            'birthDate' => '1990-01-01',
            'gender' => 'female',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Test address',
            'contactNumber' => '09123456789',
            'created_by' => 'duplicate-admin',
            ...$overrides,
        ]);
    }
}
