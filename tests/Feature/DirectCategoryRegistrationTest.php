<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ShirtSizes;
use App\Filament\Resources\Participants\Pages\CreateParticipant;
use App\Filament\Resources\Participants\Schemas\ParticipantForm;
use App\Filament\Resources\Participants\Tables\ParticipantsTable;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DirectCategoryRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_rows_preserve_timestamps_for_dashboard_sorting(): void
    {
        $category = CategoryAllocation::create([
            'categoryDescription' => 'DIRECT CATEGORY', 'allocated_participants' => 5,
        ]);
        $subcategory = Subcategory::create([
            'categoryDescription' => 'OTHER CATEGORY', 'subDescription' => 'Office', 'nop' => 3,
        ]);

        $rows = Subcategory::reportQuery()->orderByDesc('subcategories.created_at')->get();

        $this->assertCount(2, $rows);
        $this->assertTrue($rows->firstWhere('id', -$category->id)->created_at->equalTo($category->created_at));
        $this->assertTrue($rows->firstWhere('id', $subcategory->id)->created_at->equalTo($subcategory->created_at));
        $this->assertTrue($rows->firstWhere('id', -$category->id)->updated_at->equalTo($category->updated_at));

        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findByName('superadmin', 'web'));
        $this->actingAs($superadmin);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Dashboard::class)->assertCanSeeTableRecords($rows);
    }

    public function test_ao_registers_directly_until_subcategories_are_added_and_reports_retain_the_registration(): void
    {
        $category = CategoryAllocation::create(['categoryDescription' => 'DIRECT CATEGORY', 'allocated_participants' => 3]);
        $officer = User::factory()->create();
        $officer->assignRole(Role::findByName('administrative officer', 'web'));
        $officer->administeredCategoryAllocations()->attach($category);
        $this->actingAs($officer);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertArrayHasKey('DIRECT CATEGORY', ParticipantForm::categoryOptionsForUser($officer));
        $this->assertFalse(ParticipantForm::categoryHasSubcategories('DIRECT CATEGORY'));
        $this->assertSame(3, $category->distributedAllotment());

        $data = [
            'firstName' => 'Direct', 'lastName' => 'Registrant', 'birthDate' => '1990-01-02',
            'gender' => 'male', 'address' => 'Test Address', 'contactNumber' => '09123456789',
            'pwd' => '0', 'rpwuds' => '0', 'categoryDescription' => 'DIRECT CATEGORY',
            'shirtSize' => 'medium', 'distanceCategory' => '5km',
        ];
        Livewire::test(CreateParticipant::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('participants', ['categoryDescription' => 'DIRECT CATEGORY', 'subDescription' => '', 'created_by' => $officer->username]);
        $this->assertSame(1, $category->registeredParticipants());
        $this->assertSame(2, $category->registrationSlotsRemaining());
        $this->assertFalse($category->accommodatesSubcategoryCapacity(3));
        $options = ParticipantsTable::attendanceSubcategoryOptions($officer);
        $this->assertSame(['DIRECT CATEGORY — No subcategory'], array_values($options));
        $this->assertNotNull(ParticipantsTable::exportSubcategoryForUser($officer, array_key_first($options)));

        $this->actingAs($superadmin = User::factory()->create());
        $superadmin->assignRole(Role::findByName('superadmin', 'web'));
        $dashboard = Livewire::test(Dashboard::class)->assertCanSeeTableRecords(Subcategory::reportQuery()->get());
        $this->assertSame(1, (int) $dashboard->instance()->getTable()->getQuery()->first()->registered);
        $shirts = Livewire::test(ShirtSizes::class)->assertCanSeeTableRecords(Subcategory::reportQuery()->get());
        $this->assertSame(1, (int) $shirts->instance()->getTable()->getQuery()->first()->medium);
        $report = ShirtSizes::shirtSizeReportForYear(now()->year);
        $this->assertSame(1, $report['grand_total']);
        $this->assertSame('DIRECT CATEGORY', $report['groups'][0]['category']);

        Subcategory::create(['categoryDescription' => 'DIRECT CATEGORY', 'subDescription' => 'New Office', 'nop' => 2]);
        $this->assertTrue(ParticipantForm::categoryHasSubcategories('DIRECT CATEGORY'));
        $this->assertSame(3, $category->distributedAllotment());
        $this->assertSame(0, $category->remainingParticipants());
        $dashboardPage = app(Dashboard::class);
        $dashboardPage->selectedYear = now()->year;
        $breakdown = $dashboardPage->getAllocationBreakdown();
        $this->assertSame(3, $breakdown['total']);
        $this->assertSame([2, 1], array_column($breakdown['groups'][0]['items'], 'amount'));
        $this->assertSame(2, Subcategory::reportQuery()->count());
        $this->assertSame(1, ShirtSizes::shirtSizeReportForYear(now()->year)['grand_total']);

        $this->actingAs($officer);
        $data['firstName'] = 'Another';
        Livewire::test(CreateParticipant::class)->fillForm($data)->call('create')->assertHasFormErrors(['subDescription']);
        $data['subDescription'] = 'New Office';
        Livewire::test(CreateParticipant::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertSame(1, $category->registrationSlotsRemaining());
        $data['firstName'] = 'Third';
        Livewire::test(CreateParticipant::class)->fillForm($data)->call('create')->assertHasNoFormErrors();
        $this->assertSame(0, $category->registrationSlotsRemaining());
        $data['firstName'] = 'Fourth';
        Livewire::test(CreateParticipant::class)->fillForm($data)->call('create')->assertHasErrors();
        $this->assertSame(3, $category->registeredParticipants());

        $this->actingAs($superadmin);
        Livewire::test(Dashboard::class)->assertCanSeeTableRecords(Subcategory::reportQuery()->get());
        Livewire::test(ShirtSizes::class)->assertCanSeeTableRecords(Subcategory::reportQuery()->get());
        $this->assertSame(3, ShirtSizes::shirtSizeReportForYear(now()->year)['grand_total']);
    }

    public function test_category_with_no_subcategories_blocks_registration_when_full(): void
    {
        $category = CategoryAllocation::create(['categoryDescription' => 'FULL CATEGORY', 'allocated_participants' => 0]);
        $officer = User::factory()->create();
        $officer->assignRole(Role::findByName('administrative officer', 'web'));
        $officer->administeredCategoryAllocations()->attach($category);
        $this->actingAs($officer);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(CreateParticipant::class)->fillForm([
            'firstName' => 'Full', 'lastName' => 'Registrant', 'birthDate' => '1990-01-02',
            'gender' => 'male', 'address' => 'Test Address', 'contactNumber' => '09123456789',
            'pwd' => '0', 'rpwuds' => '0', 'categoryDescription' => 'FULL CATEGORY',
            'shirtSize' => 'medium', 'distanceCategory' => '5km',
        ])->call('create')->assertHasErrors();

        $this->assertSame(0, Participant::count());
    }
}
