<?php

namespace Tests\Feature;

use App\Filament\Pages\ParticipantReports;
use App\Models\CategoryAllocation;
use App\Models\Participant;
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

    public function test_ao_reports_are_limited_to_assigned_categories_and_include_direct_registrations(): void
    {
        $officer = $this->user('administrative officer');
        $category = CategoryAllocation::create(['categoryDescription' => 'ASSIGNED', 'allocated_participants' => 10]);
        $officer->administeredCategoryAllocations()->attach($category);
        $this->participant(['firstName' => 'Own', 'categoryDescription' => 'ASSIGNED', 'subDescription' => '']);
        $this->participant(['firstName' => 'Other', 'categoryDescription' => 'OTHER']);

        $report = ParticipantAgeReport::build($officer, now()->year, 'youngest', 'male', '5km');
        $this->assertCount(1, $report['sections']);
        $this->assertSame(['Own Runner'], array_column($report['sections'][0]['participants'], 'name'));
        $this->assertSame('No subcategory', $report['sections'][0]['participants'][0]['subcategory']);
        $this->assertSame('ASSIGNED', $report['scope']);

        $officer->administeredCategoryAllocations()->detach();
        $empty = ParticipantAgeReport::build($officer, now()->year, 'oldest', 'male', '5km');
        $this->assertSame([], $empty['sections'][0]['participants']);
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

    public function test_couple_and_family_lists_group_members_and_respect_year_and_ao_scope(): void
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
        $this->assertCount(1, $family['groups']);
        $this->assertSame(2, $family['total_runners']);
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
