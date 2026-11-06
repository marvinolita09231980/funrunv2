<?php

namespace Tests\Feature;

use App\Filament\Pages\ParticipantReports;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\Subcategory;
use App\Models\User;
use App\Services\ParticipantAgeReport;
use App\Services\RegistrationGroupReport;
use Barryvdh\DomPDF\Facade\Pdf;
use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ParticipantAgeReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_report_role_lists_existing_runners_even_when_created_by_other_users(): void
    {
        $viewer = $this->user('report viewer');
        $viewer->givePermissionTo(Permission::findOrCreate(ParticipantReports::VIEW_PERMISSION, 'web'));

        foreach (['1992-09-14', '1999-03-31', '1999-08-21', '1996-09-30', '1970-05-03'] as $birthDate) {
            $this->participant(['year' => 2026, 'gender' => 'male', 'distanceCategory' => '10km', 'birthDate' => $birthDate, 'created_by' => 'another-focal']);
        }
        $this->participant(['year' => 2026, 'gender' => 'female', 'distanceCategory' => '5km', 'birthDate' => '1962-09-14']);
        foreach (['2001-04-21', '1988-01-01'] as $birthDate) {
            $this->participant(['year' => 2026, 'gender' => 'female', 'distanceCategory' => '10km', 'birthDate' => $birthDate]);
        }
        for ($index = 0; $index < 7; $index++) {
            $this->participant(['year' => 2026, 'distanceCategory' => '3km']);
        }

        $report = ParticipantAgeReport::build($viewer, 2026);
        $this->assertSame([0, 5, 1, 2, 0, 5, 1, 2], array_map(fn (array $section): int => count($section['participants']), $report['sections']));
        $this->assertSame('05/03/1970', $report['sections'][1]['participants'][0]['birth_date']);
        $this->assertSame('08/21/1999', $report['sections'][5]['participants'][0]['birth_date']);
    }

    public function test_eight_rankings_select_the_correct_ten_in_each_gender_and_distance(): void
    {
        $admin = $this->user('admin');
        foreach (['male', 'female'] as $gender) {
            foreach (['5km', '10km'] as $distance) {
                for ($index = 0; $index < 12; $index++) {
                    $this->participant([
                        'firstName' => "Runner{$index}", 'gender' => strtoupper($gender),
                        'distanceCategory' => str_replace('km', ' K', $distance),
                        'birthDate' => (1960 + $index).'-01-01',
                    ]);
                }
            }
        }
        $this->participant(['firstName' => 'Wrong year', 'year' => now()->year - 1, 'birthDate' => '1940-01-01']);
        $this->participant(['firstName' => 'Wrong distance', 'distanceCategory' => '3km', 'birthDate' => '1940-01-01']);
        $this->participant(['firstName' => 'Future birth', 'birthDate' => '2099-01-01']);

        $report = ParticipantAgeReport::build($admin, now()->year);
        $this->assertCount(8, $report['sections']);
        foreach ($report['sections'] as $index => $section) {
            $rows = $section['participants'];
            $this->assertCount(10, $rows);
            $this->assertSame($index < 4 ? 'Runner0 Runner' : 'Runner11 Runner', $rows[0]['name']);
            $this->assertSame($index < 4 ? 'Runner9 Runner' : 'Runner2 Runner', $rows[9]['name']);
            $this->assertSame(range(1, 10), array_column($rows, 'rank'));
        }

        $pdf = Pdf::loadView('pdf.participant-age-report', ['report' => $report])->setPaper('a4', 'landscape')->output();
        $this->assertStringStartsWith('%PDF', $pdf);
    }

    public function test_ao_reports_include_all_categories_and_direct_registrations(): void
    {
        $officer = $this->user('administrative officer');
        $category = CategoryAllocation::create(['categoryDescription' => 'ASSIGNED', 'allocated_participants' => 10]);
        $officer->administeredCategoryAllocations()->attach($category);
        $this->participant(['firstName' => 'Own', 'categoryDescription' => 'ASSIGNED', 'subDescription' => '']);
        $this->participant(['firstName' => 'Other', 'categoryDescription' => 'OTHER']);

        $report = ParticipantAgeReport::build($officer, now()->year, 'youngest', 'male', '5km');
        $this->assertCount(1, $report['sections']);
        $this->assertSame(['Other Runner', 'Own Runner'], array_column($report['sections'][0]['participants'], 'name'));
        $this->assertSame('No subcategory', $report['sections'][0]['participants'][0]['subcategory']);
        $this->assertSame('All categories', $report['scope']);

        $officer->administeredCategoryAllocations()->detach();
        $empty = ParticipantAgeReport::build($officer, now()->year, 'oldest', 'male', '5km');
        $this->assertCount(2, $empty['sections'][0]['participants']);
    }

    public function test_focal_with_report_permission_includes_all_subcategories(): void
    {
        $focal = $this->user('focal user');
        $focal->givePermissionTo(Permission::findOrCreate(ParticipantReports::VIEW_PERMISSION, 'web'));
        Subcategory::create(['categoryDescription' => 'CATEGORY', 'subDescription' => 'OWN OFFICE', 'nop' => 10, 'username' => $focal->username]);
        $this->participant(['firstName' => 'Visible', 'categoryDescription' => 'CATEGORY', 'subDescription' => 'OWN OFFICE']);
        $this->participant(['firstName' => 'Hidden', 'categoryDescription' => 'CATEGORY', 'subDescription' => 'OTHER OFFICE']);

        $report = ParticipantAgeReport::build($focal, now()->year, 'oldest', 'male', '5km');
        $this->assertSame(['Hidden Runner', 'Visible Runner'], array_column($report['sections'][0]['participants'], 'name'));
    }

    public function test_report_page_downloads_pdfs_and_restricts_focal_access(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->user('superadmin'));
        Livewire::test(ParticipantReports::class)
            ->assertSee('Download All 8 Reports')
            ->call('downloadReport', 'oldest', 'female', '10km')
            ->assertFileDownloaded('top-10-oldest-female-10km-'.now()->year.'.pdf');

        $this->actingAs($this->user('focal user'));
        $this->assertFalse(ParticipantReports::canAccess());
        Livewire::test(ParticipantReports::class)->assertForbidden();
    }

    public function test_couple_and_family_lists_group_members_across_categories_for_selected_year(): void
    {
        $officer = $this->user('administrative officer');
        $category = CategoryAllocation::create(['categoryDescription' => 'ASSIGNED', 'allocated_participants' => 20]);
        $officer->administeredCategoryAllocations()->attach($category);

        foreach (['Primary', 'Partner'] as $name) {
            $this->participant(['firstName' => $name, 'categoryDescription' => 'ASSIGNED', 'subDescription' => 'Couple', 'couple' => true, 'participantNumber' => '100']);
        }
        foreach (['Parent', 'Child'] as $name) {
            $this->participant(['firstName' => $name, 'categoryDescription' => 'ASSIGNED', 'subDescription' => 'Family', 'participantNumber' => '200']);
        }
        $this->participant(['firstName' => 'Other category', 'categoryDescription' => 'OTHER', 'subDescription' => 'Family', 'participantNumber' => '200']);
        $this->participant(['firstName' => 'Other year', 'year' => now()->year - 1, 'categoryDescription' => 'ASSIGNED', 'subDescription' => 'Family', 'participantNumber' => '200']);
        $this->participant(['firstName' => 'Individual', 'categoryDescription' => 'ASSIGNED']);

        $couple = RegistrationGroupReport::build($officer, now()->year, 'couple');
        $family = RegistrationGroupReport::build($officer, now()->year, 'family');
        $this->assertCount(1, $couple['groups']);
        $this->assertSame(2, $couple['total_runners']);
        $this->assertSame(['Primary Runner', 'Partner Runner'], array_column($couple['groups'][0]['members'], 'name'));
        $this->assertCount(2, $family['groups']);
        $this->assertSame(3, $family['total_runners']);
        $this->assertSame(['Parent Runner', 'Child Runner'], array_column($family['groups'][0]['members'], 'name'));

        $this->actingAs($officer);
        $officer->givePermissionTo(Permission::findOrCreate(ParticipantReports::VIEW_PERMISSION, 'web'));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ParticipantReports::class)
            ->assertSee('Couple Runners PDF')->assertSee('Family Runners PDF')
            ->call('downloadRunnerList', 'couple')->assertFileDownloaded('couple-runners-'.now()->year.'.pdf')
            ->call('downloadRunnerList', 'family')->assertFileDownloaded('family-runners-'.now()->year.'.pdf');
    }

    public function test_roles_permission_controls_page_navigation_and_downloads(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $pages = FilamentShield::getPages();
        $this->assertArrayHasKey(ParticipantReports::VIEW_PERMISSION, $pages[ParticipantReports::class]['permissions']);

        $admin = $this->user('admin');
        $this->actingAs($admin);
        $this->assertFalse(ParticipantReports::canAccess());
        $this->assertFalse(ParticipantReports::shouldRegisterNavigation());
        Livewire::test(ParticipantReports::class)->assertForbidden();

        $permission = Permission::findOrCreate(ParticipantReports::VIEW_PERMISSION, 'web');
        $role = Role::findByName('admin', 'web');
        $role->givePermissionTo($permission);
        $admin->unsetRelation('roles');
        $this->assertTrue(ParticipantReports::canAccess());
        $this->assertTrue(ParticipantReports::shouldRegisterNavigation());
        $page = Livewire::test(ParticipantReports::class)->assertSee('Participant Age Reports');

        $role->revokePermissionTo($permission);
        $admin->unsetRelation('roles');
        $this->assertFalse(ParticipantReports::canAccess());
        $page->call('downloadReport')->assertForbidden();
    }

    private function user(string $role): User
    {
        $user = User::factory()->create();
        $user->assignRole(Role::findOrCreate($role, 'web'));

        return $user;
    }

    private function participant(array $attributes): Participant
    {
        return Participant::create(array_merge([
            'year' => now()->year, 'participantNumber' => '0', 'firstName' => 'Sample', 'lastName' => 'Runner',
            'categoryDescription' => 'OPEN CATEGORY', 'subDescription' => '', 'shirtSize' => 'medium',
            'birthDate' => '1990-01-01', 'gender' => 'male', 'waiver' => true,
            'distanceCategory' => '5km', 'referenceNumber' => '0', 'address' => 'Test address',
            'contactNumber' => '09123456789', 'created_by' => 'opencategory',
        ], $attributes));
    }
}
