<?php

namespace Tests\Feature;

use App\Filament\Pages\PotentialDuplicates;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Facades\Filament;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\StreamedResponse;
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

    public function test_page_displays_same_year_duplicates_across_categories(): void
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
        ]);
        $secondDuplicate = $this->participant([
            'year' => $year,
            'firstName' => '  maria ',
            'lastName' => 'SANTOS ',
            'birthDate' => '1994-06-15',
            'categoryDescription' => 'CATEGORY B',
            'subDescription' => 'Office B',
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

        Livewire::test(PotentialDuplicates::class)
            ->call('loadPotentialDuplicates')
            ->assertCanSeeTableRecords([$firstDuplicate, $secondDuplicate])
            ->assertCanNotSeeTableRecords([
                $differentBirthDate,
                $differentYear,
                $exactOnlyFirst,
                $exactOnlySecond,
            ])
            ->assertTableColumnStateSet('potential_duplicate_count', 1, $firstDuplicate)
            ->assertTableColumnStateSet('potential_duplicate_count', 1, $secondDuplicate)
            ->assertSee('Phone 09171111111')
            ->assertSee('High-Probability Groups')
            ->assertSee('Records for Review')
            ->assertSee('High Probability')
            ->assertSee('CATEGORY A')
            ->assertSee('CATEGORY B');
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
            ->assertActionExists('downloadPdfReport');
        $response = $component->instance()->downloadPdfReport();

        $this->assertInstanceOf(StreamedResponse::class, $response);
        $this->assertStringContainsString(
            "potential-duplicates-{$year}.pdf",
            (string) $response->headers->get('Content-Disposition'),
        );
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
