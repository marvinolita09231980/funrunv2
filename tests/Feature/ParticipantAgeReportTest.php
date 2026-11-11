<?php

namespace Tests\Feature;

use App\Filament\Pages\ParticipantReports;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\ParticipantReportExclusion;
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
        $this->assertSame('09123456789', $report['sections'][1]['participants'][0]['phone_number']);
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
        $html = view('pdf.participant-age-report', ['report' => $report])->render();
        $this->assertStringContainsString('Phone Number', $html);
        $this->assertStringContainsString('>Time<', $html);
        $this->assertStringContainsString('09123456789', $html);
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
            ->assertSee('View All 8 Reports')
            ->assertSee('Preview Report')
            ->assertSee('Preview Couple Report')
            ->assertDontSee('Manage Exclusions')
            ->assertDontSee('Download All 8 Reports')
            ->assertDontSee('Download Couple PDF')
            ->assertDontSee('Download Family PDF');

        $this->actingAs($this->user('focal user'));
        $this->assertFalse(ParticipantReports::canAccess());
        Livewire::test(ParticipantReports::class)->assertForbidden();
    }

    public function test_authorized_user_can_view_report_pdfs_inline(): void
    {
        $this->actingAs($this->user('superadmin'));

        $ageReports = [
            ['ranking' => 'all'],
            ['ranking' => 'oldest', 'gender' => 'female', 'distance' => '10km'],
        ];

        foreach ($ageReports as $parameters) {
            $response = $this->get(route('participant-reports.preview.age', [
                'year' => now()->year,
                ...$parameters,
            ]));

            $response
                ->assertOk()
                ->assertHeader('Content-Type', 'application/pdf');
            $this->assertStringContainsString(
                'inline; filename=',
                (string) $response->headers->get('Content-Disposition'),
            );
            $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        }

        foreach (['couple', 'family'] as $type) {
            $response = $this->get(route('participant-reports.preview.group', [
                'type' => $type,
                'year' => now()->year,
            ]));

            $response
                ->assertOk()
                ->assertHeader('Content-Type', 'application/pdf')
                ->assertHeader(
                    'Content-Disposition',
                    "inline; filename=\"{$type}-runners-".now()->year.'.pdf"',
                );
            $this->assertStringStartsWith('%PDF', (string) $response->getContent());
        }
    }

    public function test_view_pdf_actions_open_a_preview_modal_with_a_download_option(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($this->user('superadmin'));

        $year = now()->year;
        $page = Livewire::test(ParticipantReports::class)
            ->call('openAgeReportPreview', 'oldest', 'female', '10km')
            ->assertSet('previewReportType', 'age')
            ->assertSet('previewTitle', "Top 10 Oldest · Female · 10K · {$year}")
            ->assertSet('previewReportParameters.year', $year)
            ->assertSet('previewReportParameters.ranking', 'oldest')
            ->assertDispatched('open-modal', id: 'participant-report-pdf-preview')
            ->assertSee('Download PDF')
            ->call('downloadPreview')
            ->assertFileDownloaded("top-10-oldest-female-10km-{$year}.pdf");

        $page
            ->call('openRunnerListPreview', 'family')
            ->assertSet('previewReportType', 'group')
            ->assertSet('previewTitle', "Family Runner List · {$year}")
            ->assertSet('previewReportParameters.type', 'family')
            ->assertDispatched('open-modal', id: 'participant-report-pdf-preview')
            ->call('downloadPreview')
            ->assertFileDownloaded("family-runners-{$year}.pdf");
    }

    public function test_participants_can_be_excluded_directly_from_the_interactive_preview(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $superadmin = $this->user('superadmin');
        $this->actingAs($superadmin);

        $participants = collect();
        for ($index = 0; $index < 11; $index++) {
            $participants->push($this->participant([
                'firstName' => "PreviewRunner{$index}",
                'gender' => 'male',
                'distanceCategory' => '5km',
                'birthDate' => (1960 + $index).'-01-01',
            ]));
        }

        $page = Livewire::test(ParticipantReports::class)
            ->call('openAgeReportPreview', 'oldest', 'male', '5km')
            ->assertSee('PreviewRunner0 Runner')
            ->assertSee('Exclude')
            ->call(
                'excludePreviewParticipant',
                $participants[0]->getKey(),
                'oldest',
                'male',
                '5km',
            )
            ->assertHasNoErrors()
            ->assertSee('PreviewRunner10 Runner');

        $preview = $page->instance()->previewReport();
        $previewNames = array_column($preview['sections'][0]['participants'], 'name');
        $this->assertCount(10, $previewNames);
        $this->assertNotContains('PreviewRunner0 Runner', $previewNames);
        $this->assertContains('PreviewRunner10 Runner', $previewNames);
        $this->assertDatabaseHas('participant_report_exclusions', [
            'participant_id' => $participants[0]->getKey(),
            'year' => now()->year,
            'report_key' => ParticipantReportExclusion::ageKey('oldest', 'male', '5km'),
            'excluded_by' => $superadmin->getKey(),
        ]);

        $coupleRunner = $this->participant([
            'firstName' => 'Preview Couple',
            'subDescription' => 'Couple',
            'couple' => true,
            'participantNumber' => 'PREVIEW-C-1',
        ]);

        $page
            ->call('openRunnerListPreview', 'couple')
            ->assertSee('Preview Couple Runner')
            ->call('excludePreviewParticipant', $coupleRunner->getKey())
            ->assertHasNoErrors();

        $this->assertDatabaseHas('participant_report_exclusions', [
            'participant_id' => $coupleRunner->getKey(),
            'year' => now()->year,
            'report_key' => ParticipantReportExclusion::groupKey('couple'),
            'excluded_by' => $superadmin->getKey(),
        ]);
    }

    public function test_each_report_can_exclude_and_include_participants_independently(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $superadmin = $this->user('superadmin');
        $this->actingAs($superadmin);

        $year = now()->year;
        $participants = collect();

        for ($index = 0; $index < 12; $index++) {
            $participants->push($this->participant([
                'firstName' => "Runner{$index}",
                'gender' => 'male',
                'distanceCategory' => '5km',
                'birthDate' => (1960 + $index).'-01-01',
            ]));
        }

        $oldestBefore = ParticipantAgeReport::build(
            $superadmin,
            $year,
            'oldest',
            'male',
            '5km',
        );
        $youngestBefore = ParticipantAgeReport::build(
            $superadmin,
            $year,
            'youngest',
            'male',
            '5km',
        );
        $this->assertSame('Runner0 Runner', $oldestBefore['sections'][0]['participants'][0]['name']);

        $page = Livewire::test(ParticipantReports::class)
            ->call('openAgeReportPreview', 'oldest', 'male', '5km')
            ->call(
                'excludePreviewParticipant',
                $participants[0]->getKey(),
                'oldest',
                'male',
                '5km',
            )
            ->assertSee('Include back');

        $includedParticipants = $page->instance()->previewReport()['sections'][0]['participants'];
        $excludedSections = $page->instance()->previewExcludedSections();
        $this->assertCount(10, $includedParticipants);
        $this->assertCount(1, $excludedSections);
        $this->assertCount(1, $excludedSections[0]['participants']);
        $this->assertSame($participants[0]->getKey(), $excludedSections[0]['participants'][0]['participant_id']);
        $this->assertSame(
            (int) \Carbon\CarbonImmutable::parse($participants[0]->birthDate)->diffInYears(\Carbon\CarbonImmutable::today()),
            $excludedSections[0]['participants'][0]['age'],
        );
        $this->assertContains($participants[10]->getKey(), array_column($includedParticipants, 'participant_id'));

        $this->assertDatabaseHas('participant_report_exclusions', [
            'participant_id' => $participants[0]->getKey(),
            'year' => $year,
            'report_key' => ParticipantReportExclusion::ageKey('oldest', 'male', '5km'),
            'excluded_by' => $superadmin->getKey(),
        ]);

        $oldestAfter = ParticipantAgeReport::build(
            $superadmin,
            $year,
            'oldest',
            'male',
            '5km',
        );
        $youngestUnaffected = ParticipantAgeReport::build(
            $superadmin,
            $year,
            'youngest',
            'male',
            '5km',
        );

        $this->assertSame('Runner1 Runner', $oldestAfter['sections'][0]['participants'][0]['name']);
        $this->assertContains('Runner10 Runner', array_column(
            $oldestAfter['sections'][0]['participants'],
            'name',
        ));
        $this->assertSame(
            array_column($youngestBefore['sections'][0]['participants'], 'name'),
            array_column($youngestUnaffected['sections'][0]['participants'], 'name'),
        );

        $page
            ->call(
                'includePreviewParticipant',
                $participants[0]->getKey(),
                ParticipantReportExclusion::ageKey('oldest', 'male', '5km'),
            );

        $this->assertDatabaseMissing('participant_report_exclusions', [
            'participant_id' => $participants[0]->getKey(),
            'year' => $year,
            'report_key' => ParticipantReportExclusion::ageKey('oldest', 'male', '5km'),
        ]);
        $this->assertSame(
            'Runner0 Runner',
            ParticipantAgeReport::build(
                $superadmin,
                $year,
                'oldest',
                'male',
                '5km',
            )['sections'][0]['participants'][0]['name'],
        );
    }

    public function test_couple_and_family_exclusions_are_independent(): void
    {
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $superadmin = $this->user('superadmin');
        $this->actingAs($superadmin);

        $coupleRunner = $this->participant([
            'firstName' => 'Couple Primary',
            'subDescription' => 'Couple',
            'couple' => true,
            'participantNumber' => 'C-100',
        ]);
        $this->participant([
            'firstName' => 'Couple Partner',
            'subDescription' => 'Couple',
            'couple' => true,
            'participantNumber' => 'C-100',
        ]);
        $familyRunner = $this->participant([
            'firstName' => 'Family Parent',
            'subDescription' => 'Family',
            'couple' => false,
            'participantNumber' => 'F-100',
        ]);

        Livewire::test(ParticipantReports::class)
            ->call('openRunnerListPreview', 'couple')
            ->call('excludePreviewParticipant', $coupleRunner->getKey())
            ->assertSee('Include back');

        $couple = RegistrationGroupReport::build($superadmin, now()->year, 'couple');
        $family = RegistrationGroupReport::build($superadmin, now()->year, 'family');

        $this->assertSame(
            ['Couple Partner Runner'],
            array_column($couple['groups'][0]['members'], 'name'),
        );
        $this->assertSame(
            ['Family Parent Runner'],
            array_column($family['groups'][0]['members'], 'name'),
        );
        $this->assertDatabaseHas('participant_report_exclusions', [
            'participant_id' => $coupleRunner->getKey(),
            'report_key' => ParticipantReportExclusion::groupKey('couple'),
        ]);
        $this->assertDatabaseMissing('participant_report_exclusions', [
            'participant_id' => $familyRunner->getKey(),
            'report_key' => ParticipantReportExclusion::groupKey('family'),
        ]);
    }

    public function test_pdf_preview_routes_enforce_report_permissions(): void
    {
        $this->actingAs($this->user('focal user'));

        $this->get(route('participant-reports.preview.age', [
            'year' => now()->year,
            'ranking' => 'all',
        ]))->assertForbidden();

        $this->get(route('participant-reports.preview.group', [
            'type' => 'couple',
            'year' => now()->year,
        ]))->assertForbidden();
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
        $this->assertSame('09123456789', $couple['groups'][0]['members'][0]['phone_number']);
        $this->assertCount(2, $family['groups']);
        $this->assertSame(3, $family['total_runners']);
        $this->assertSame(['Parent Runner', 'Child Runner'], array_column($family['groups'][0]['members'], 'name'));
        $groupHtml = view('pdf.registration-group-report', ['report' => $couple])->render();
        $this->assertStringContainsString('Phone Number', $groupHtml);
        $this->assertStringContainsString('>Time<', $groupHtml);

        $this->actingAs($officer);
        $officer->givePermissionTo(Permission::findOrCreate(ParticipantReports::VIEW_PERMISSION, 'web'));
        Filament::setCurrentPanel(Filament::getPanel('admin'));
        Livewire::test(ParticipantReports::class)
            ->assertSee('Preview Couple Report')->assertSee('Preview Family Report')
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
