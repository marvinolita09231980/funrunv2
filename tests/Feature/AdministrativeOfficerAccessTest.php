<?php

namespace Tests\Feature;

use App\Filament\Resources\Subcategories\SubcategoriesResource;
use App\Filament\Resources\Subcategories\Pages\EditSubcategories;
use App\Filament\Resources\Subcategories\Pages\ListSubcategories;
use App\Filament\Resources\Subcategories\Widgets\SubcategoryAllocationOverview;
use App\Filament\Resources\CategoryAllocations\CategoryAllocationResource;
use App\Filament\Resources\CategoryAllocations\Pages\EditCategoryAllocation;
use App\Filament\Resources\CategoryAllocations\Pages\ListCategoryAllocations;
use App\Filament\Resources\Participants\Schemas\ParticipantForm;
use App\Filament\Resources\Participants\Tables\ParticipantsTable;
use App\Filament\Resources\Participants\Pages\ListParticipants;
use App\Filament\Resources\Participants\Widgets\ParticipantAllocationOverview;
use App\Filament\Resources\Subcategories\Schemas\SubcategoriesForm;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\UserResource;
use App\Models\Participant;
use App\Models\CategoryAllocation;
use App\Models\OverallAllocation;
use App\Models\Subcategory;
use App\Models\User;
use App\Policies\ParticipantPolicy;
use App\Policies\CategoryAllocationPolicy;
use App\Policies\SubcategoryPolicy;
use App\Policies\UserPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdministrativeOfficerAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrative_officer_is_limited_to_the_assigned_category(): void
    {
        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        $categoryBAllocation = CategoryAllocation::query()->firstOrCreate(
            ['categoryDescription' => 'CATEGORY B'],
            ['allocated_participants' => 0],
        );
        $officer->administeredCategoryAllocations()->attach($categoryBAllocation);

        $categoryA = $this->createSubcategory('CATEGORY A', 'Office A');
        $this->createSubcategory('CATEGORY A', 'Office A Two');
        $categoryB = $this->createSubcategory('CATEGORY B', 'Office B');
        $categoryC = $this->createSubcategory('CATEGORY C', 'Office C');

        $visibleSubcategories = SubcategoriesResource::applyVisibilityForUser(
            Subcategory::query(),
            $officer,
        )->get();

        $this->assertCount(3, $visibleSubcategories);
        $this->assertTrue($visibleSubcategories->every(
            fn (Subcategory $subcategory): bool => in_array(
                $subcategory->categoryDescription,
                ['CATEGORY A', 'CATEGORY B'],
                true,
            ),
        ));
        $this->assertSame(
            ['CATEGORY A', 'CATEGORY B'],
            $officer->administeredCategoryNames(),
        );
        $this->assertSame(
            ['CATEGORY A' => 'CATEGORY A', 'CATEGORY B' => 'CATEGORY B'],
            ParticipantForm::categoryOptionsForUser($officer),
        );

        $policy = new SubcategoryPolicy();

        $this->assertTrue($policy->viewAny($officer));
        $this->assertTrue($policy->view($officer, $categoryA));
        $this->assertTrue($policy->update($officer, $categoryA));
        $this->assertTrue($policy->view($officer, $categoryB));
        $this->assertTrue($policy->update($officer, $categoryB));
        $this->assertFalse($policy->view($officer, $categoryC));
        $this->assertFalse($policy->update($officer, $categoryC));
        $this->assertFalse($policy->create($officer));
        $this->assertFalse($policy->delete($officer, $categoryA));
        $this->assertTrue($officer->canAccessPanel(app('filament')->getPanel('admin')));
    }

    public function test_focal_user_can_be_assigned_and_capacity_can_be_changed(): void
    {
        $focalRole = Role::query()->firstOrCreate([
            'name' => 'focal user',
            'guard_name' => 'web',
        ]);
        $focalUser = $this->createUser('Focal Person', 'focal-person');
        $focalUser->assignRole($focalRole);

        $subcategory = $this->createSubcategory('CATEGORY A', 'Office A');
        $subcategory->update([
            'nop' => 250,
            'username' => $focalUser->username,
        ]);

        $this->assertSame(250, $subcategory->fresh()->nop);
        $this->assertSame('Focal Person', $subcategory->fresh()->focal_user_name);
    }

    public function test_officer_can_view_all_participants_but_manage_only_the_assigned_category(): void
    {
        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        $officeA = $this->createSubcategory('CATEGORY A', 'Office A');
        $officeATwo = $this->createSubcategory('CATEGORY A', 'Office A Two');
        $officeB = $this->createSubcategory('CATEGORY B', 'Office B');

        $categoryAParticipant = $this->createParticipant('Category A', 'CATEGORY A');
        $categoryBParticipant = $this->createParticipant('Category B', 'CATEGORY B');

        $visibleParticipants = ParticipantsTable::applyVisibilityForUser(
            Participant::query(),
            $officer,
        )->get();

        $this->assertCount(2, $visibleParticipants);

        $policy = new ParticipantPolicy();

        $this->assertTrue($policy->viewAny($officer));
        $this->assertTrue($policy->view($officer, $categoryAParticipant));
        $this->assertTrue($policy->view($officer, $categoryBParticipant));
        $this->assertTrue($policy->create($officer));
        $this->assertTrue($policy->update($officer, $categoryAParticipant));
        $this->assertTrue($policy->delete($officer, $categoryAParticipant));
        $this->assertFalse($policy->update($officer, $categoryBParticipant));
        $this->assertFalse($policy->delete($officer, $categoryBParticipant));

        $this->assertSame(
            ['CATEGORY A' => 'CATEGORY A'],
            ParticipantForm::categoryOptionsForUser($officer),
        );
        $this->assertSame(
            ['Office A' => 'Office A', 'Office A Two' => 'Office A Two'],
            ParticipantForm::subcategoryOptionsForUser($officer, 'CATEGORY A'),
        );
        $this->assertSame([], ParticipantForm::subcategoryOptionsForUser($officer, 'CATEGORY B'));
        $this->assertTrue(ParticipantsTable::canUseAttendanceExports($officer));
        $this->assertSame([
            $officeA->getKey() => 'CATEGORY A — Office A',
            $officeATwo->getKey() => 'CATEGORY A — Office A Two',
        ], ParticipantsTable::attendanceSubcategoryOptions($officer));
        $this->assertSame(
            $officeA->getKey(),
            ParticipantsTable::exportSubcategoryForUser($officer, $officeA->getKey())?->getKey(),
        );
        $this->assertNull(
            ParticipantsTable::exportSubcategoryForUser($officer, $officeB->getKey()),
        );

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($officer);

        Livewire::test(ListParticipants::class)
            ->assertTableActionVisible('attendance_export')
            ->assertTableActionVisible('food_attendance_export');
    }

    public function test_main_category_allocation_limits_ao_subcategory_distribution(): void
    {
        $adminRole = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $admin = $this->createUser('Administrator', 'admin-user');
        $admin->assignRole($adminRole);

        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        $categoryAAllocation = CategoryAllocation::query()->updateOrCreate(
            ['categoryDescription' => 'CATEGORY A'],
            ['allocated_participants' => 300],
        );
        CategoryAllocation::query()->updateOrCreate(
            ['categoryDescription' => 'CATEGORY B'],
            ['allocated_participants' => 200],
        );
        $overallAllocation = OverallAllocation::current();
        $overallAllocation->update(['total_participants' => 600]);

        $firstSubcategory = $this->createSubcategory('CATEGORY A', 'Office A');
        $firstSubcategory->update(['nop' => 100]);
        $secondSubcategory = $this->createSubcategory('CATEGORY A', 'Office A Two');
        $secondSubcategory->update(['nop' => 80]);

        $this->assertSame(180, $categoryAAllocation->distributedParticipants());
        $this->assertSame(120, $categoryAAllocation->remainingParticipants());
        $this->assertSame(
            'Administrative Officer',
            $categoryAAllocation->fresh()->assigned_ao_names,
        );
        $this->assertSame(500, $overallAllocation->assignedParticipants());
        $this->assertSame(100, $overallAllocation->remainingParticipants());
        $this->assertTrue($overallAllocation->canSetTotal(500));
        $this->assertFalse($overallAllocation->canSetTotal(499));
        $this->assertTrue($overallAllocation->canAllocateToCategory(
            400,
            $categoryAAllocation->getKey(),
        ));
        $this->assertFalse($overallAllocation->canAllocateToCategory(
            401,
            $categoryAAllocation->getKey(),
        ));
        $this->assertTrue($categoryAAllocation->canSetAllocation(180));
        $this->assertFalse($categoryAAllocation->canSetAllocation(179));
        $this->assertTrue($categoryAAllocation->accommodatesSubcategoryCapacity(
            220,
            $firstSubcategory->getKey(),
        ));
        $this->assertFalse($categoryAAllocation->accommodatesSubcategoryCapacity(
            221,
            $firstSubcategory->getKey(),
        ));

        $visibleAllocations = CategoryAllocationResource::applyVisibilityForUser(
            CategoryAllocation::query(),
            $officer,
        )->get();

        $this->assertCount(1, $visibleAllocations);
        $this->assertSame('CATEGORY A', $visibleAllocations->first()->categoryDescription);

        $policy = new CategoryAllocationPolicy();

        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $categoryAAllocation));
        $this->assertTrue($policy->view($officer, $categoryAAllocation));
        $this->assertFalse($policy->update($officer, $categoryAAllocation));
    }

    public function test_ao_participant_header_deducts_ao_and_focal_registrations(): void
    {
        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        $allocation = CategoryAllocation::query()
            ->where('categoryDescription', 'CATEGORY A')
            ->firstOrFail();
        $allocation->update(['allocated_participants' => 10]);

        $focalUser = $this->createUser('Focal Person', 'focal-person');
        $focalUser->assignRole(Role::findByName('focal user'));

        $this->createParticipant('Added By Officer', 'CATEGORY A')
            ->update(['created_by' => $officer->username]);
        $this->createParticipant('Added By Focal', 'CATEGORY A')
            ->update(['created_by' => $focalUser->username]);
        $this->createParticipant('Other Category', 'CATEGORY B');

        $this->assertSame(2, $allocation->registeredParticipants());
        $this->assertSame(8, $allocation->registrationSlotsRemaining());

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($officer);

        Livewire::test(ParticipantAllocationOverview::class)
            ->assertSee('Your Category Participant Allocation')
            ->assertSee('CATEGORY A')
            ->assertSee('8 remaining')
            ->assertSee('10 allocated')
            ->assertSee('2 registered');
    }

    public function test_subcategory_table_shows_registered_count_and_category_allocation_summary(): void
    {
        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        $allocation = CategoryAllocation::query()
            ->where('categoryDescription', 'CATEGORY A')
            ->firstOrFail();
        $allocation->update(['allocated_participants' => 10]);

        $firstSubcategory = $this->createSubcategory('CATEGORY A', 'Office A');
        $firstSubcategory->update(['nop' => 6]);
        $secondSubcategory = $this->createSubcategory('CATEGORY A', 'Office A Two');
        $secondSubcategory->update(['nop' => 2]);

        $this->createParticipant('First Registered', 'CATEGORY A', 'Office A');
        $this->createParticipant('Second Registered', 'CATEGORY A', 'Office A Two');
        $this->createParticipant('Other Category', 'CATEGORY B', 'Office B');

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($officer);

        Livewire::test(ListSubcategories::class)
            ->assertTableColumnExists('registered_participants_count')
            ->assertTableColumnStateSet('registered_participants_count', 1, $firstSubcategory)
            ->assertTableColumnStateSet('registered_participants_count', 1, $secondSubcategory);

        Livewire::test(SubcategoryAllocationOverview::class)
            ->assertSee('Category Allocation for Subcategories')
            ->assertSee('CATEGORY A')
            ->assertSee('10 allocated')
            ->assertSee('8 allotted to subcategories')
            ->assertSee('2 free for allotment');
    }

    public function test_admin_can_set_the_overall_allocation_but_officer_cannot(): void
    {
        $adminRole = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $admin = $this->createUser('Administrator', 'admin-user');
        $admin->assignRole($adminRole);

        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        CategoryAllocation::query()->updateOrCreate(
            ['categoryDescription' => 'CATEGORY A'],
            ['allocated_participants' => 300],
        );
        $categoryBAllocation = CategoryAllocation::query()->updateOrCreate(
            ['categoryDescription' => 'CATEGORY B'],
            ['allocated_participants' => 200],
        );

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(ListCategoryAllocations::class)
            ->assertActionVisible('setOverallAllocation')
            ->callAction('setOverallAllocation', [
                'total_participants' => 499,
            ])
            ->assertHasActionErrors(['total_participants']);

        Livewire::test(ListCategoryAllocations::class)
            ->callAction('setOverallAllocation', [
                'total_participants' => 750,
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(750, OverallAllocation::current()->fresh()->total_participants);
        $this->assertSame(250, OverallAllocation::current()->remainingParticipants());

        Livewire::test(EditCategoryAllocation::class, [
            'record' => $categoryBAllocation->getRouteKey(),
        ])
            ->fillForm([
                'administrativeOfficers' => [$officer->getKey()],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($officer->administersCategory('CATEGORY A'));
        $this->assertTrue($officer->administersCategory('CATEGORY B'));
        $this->assertSame(
            'Administrative Officer',
            $categoryBAllocation->fresh()->assigned_ao_names,
        );

        $this->actingAs($officer);

        Livewire::test(ListCategoryAllocations::class)
            ->assertActionHidden('setOverallAllocation');
    }

    public function test_category_allocation_cannot_be_reduced_below_registered_participants(): void
    {
        $adminRole = Role::query()->firstOrCreate([
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $admin = $this->createUser('Administrator', 'admin-user');
        $admin->assignRole($adminRole);

        $allocation = CategoryAllocation::query()->create([
            'categoryDescription' => 'OPEN CATEGORY',
            'allocated_participants' => 10,
        ]);
        OverallAllocation::current()->update(['total_participants' => 10]);

        foreach (range(1, 6) as $number) {
            $this->createParticipant("Open {$number}", 'OPEN CATEGORY', 'Individual');
        }

        $this->assertSame(6, $allocation->registeredParticipants());
        $this->assertSame(6, $allocation->minimumAllocation());
        $this->assertFalse($allocation->canSetAllocation(5));
        $this->assertTrue($allocation->canSetAllocation(6));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($admin);

        Livewire::test(EditCategoryAllocation::class, [
            'record' => $allocation->getRouteKey(),
        ])
            ->fillForm(['allocated_participants' => 5])
            ->call('save')
            ->assertHasFormErrors(['allocated_participants']);

        $this->assertSame(10, $allocation->fresh()->allocated_participants);

        Livewire::test(EditCategoryAllocation::class, [
            'record' => $allocation->getRouteKey(),
        ])
            ->fillForm(['allocated_participants' => 6])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(6, $allocation->fresh()->allocated_participants);
    }

    public function test_subcategory_capacity_cannot_be_reduced_below_registered_participants(): void
    {
        $focalRole = Role::query()->firstOrCreate([
            'name' => 'focal user',
            'guard_name' => 'web',
        ]);
        $focalUser = $this->createUser('Focal Person', 'focal-person');
        $focalUser->forceFill(['assigned_category' => 'CATEGORY A'])->save();
        $focalUser->assignRole($focalRole);

        CategoryAllocation::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'allocated_participants' => 10,
        ]);
        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        $subcategory = Subcategory::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'nop' => 10,
            'username' => $focalUser->username,
        ]);

        foreach (range(1, 6) as $number) {
            $this->createParticipant("Office {$number}", 'CATEGORY A', 'Office A');
        }

        $this->assertSame(6, $subcategory->minimumCapacity());
        $this->assertFalse($subcategory->canSetCapacity(5));
        $this->assertTrue($subcategory->canSetCapacity(6));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($officer);

        Livewire::test(EditSubcategories::class, [
            'record' => $subcategory->getRouteKey(),
        ])
            ->fillForm(['nop' => 5])
            ->call('save')
            ->assertHasFormErrors(['nop']);

        $this->assertSame(10, $subcategory->fresh()->nop);

        Livewire::test(EditSubcategories::class, [
            'record' => $subcategory->getRouteKey(),
        ])
            ->fillForm(['nop' => 6])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(6, $subcategory->fresh()->nop);
    }

    public function test_officer_can_create_only_focal_users_for_the_administered_category(): void
    {
        Role::query()->firstOrCreate([
            'name' => 'focal user',
            'guard_name' => 'web',
        ]);

        $this->createSubcategory('CATEGORY A', 'Office A');
        $this->createSubcategory('CATEGORY B', 'Office B');

        $officer = $this->createUser(
            name: 'Administrative Officer',
            username: 'ao-one',
            administeredCategory: 'CATEGORY A',
        );
        $officer->assignRole(Role::findByName('administrative officer'));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($officer);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Category A Focal',
                'office' => 'Category A Office',
                'assigned_category' => 'CATEGORY A',
                'password' => 'secret-password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $focalUser = User::query()
            ->where('username', 'categoryafocal')
            ->firstOrFail();

        $this->assertSame('categoryafocal', $focalUser->username);
        $this->assertNull($focalUser->email);
        $this->assertSame('CATEGORY A', $focalUser->assigned_category);
        $this->assertSame('ao-one', $focalUser->created_by);
        $this->assertTrue($focalUser->hasRole('focal user'));
        $this->assertFalse($focalUser->hasRole('administrative officer'));
        $this->assertTrue(Hash::check('secret-password', $focalUser->password));

        $otherFocalUser = $this->createUser('Category B Focal', 'category-b-focal');
        $otherFocalUser->update(['assigned_category' => 'CATEGORY B']);
        $otherFocalUser->assignRole(Role::findByName('focal user'));

        $sameCategoryOtherFocal = $this->createUser('Other Category A Focal', 'other-category-a-focal');
        $sameCategoryOtherFocal->update([
            'assigned_category' => 'CATEGORY A',
            'created_by' => 'different-officer',
        ]);
        $sameCategoryOtherFocal->assignRole(Role::findByName('focal user'));

        $visibleUsers = UserResource::applyVisibilityForUser(User::query(), $officer)->get();

        $this->assertCount(2, $visibleUsers);
        $this->assertTrue($visibleUsers->contains($focalUser));
        $this->assertTrue($visibleUsers->contains($sameCategoryOtherFocal));
        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$focalUser, $sameCategoryOtherFocal])
            ->assertCanNotSeeTableRecords([$otherFocalUser])
            ->assertTableActionVisible('edit', $focalUser)
            ->assertTableActionVisible('delete', $focalUser)
            ->assertTableActionVisible('impersonate', $focalUser)
            ->assertTableActionHidden('edit', $sameCategoryOtherFocal)
            ->assertTableActionHidden('delete', $sameCategoryOtherFocal)
            ->assertTableActionVisible('impersonate', $sameCategoryOtherFocal);

        Livewire::test(EditUser::class, ['record' => $focalUser->getRouteKey()])
            ->fillForm(['office' => 'Updated Category A Office'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Updated Category A Office', $focalUser->fresh()->office);
        $this->assertTrue(Hash::check('secret-password', $focalUser->fresh()->password));

        Livewire::test(EditUser::class, ['record' => $focalUser->getRouteKey()])
            ->fillForm(['password' => 'updated-secret-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue(Hash::check('updated-secret-password', $focalUser->fresh()->password));
        $this->assertFalse(Hash::check('secret-password', $focalUser->fresh()->password));
        $this->assertSame(
            [
                'categoryafocal' => 'Category A Focal',
                'other-category-a-focal' => 'Other Category A Focal',
            ],
            SubcategoriesForm::focalUserOptionsForCategory('CATEGORY A'),
        );
        $this->assertSame(
            ['category-b-focal' => 'Category B Focal'],
            SubcategoriesForm::focalUserOptionsForCategory('CATEGORY B'),
        );

        $policy = new UserPolicy();

        $this->assertTrue($policy->viewAny($officer));
        $this->assertTrue($policy->create($officer));
        $this->assertTrue($policy->view($officer, $focalUser));
        $this->assertFalse($policy->view($officer, $otherFocalUser));
        $this->assertTrue($policy->update($officer, $focalUser));
        $this->assertTrue($policy->delete($officer, $focalUser));
        $this->assertFalse($policy->update($officer, $sameCategoryOtherFocal));
        $this->assertFalse($policy->delete($officer, $sameCategoryOtherFocal));
        $this->assertTrue($officer->canImpersonate());
        $this->assertTrue($focalUser->canBeImpersonated());
        $this->assertTrue($sameCategoryOtherFocal->canBeImpersonated());
        $this->assertFalse($otherFocalUser->canBeImpersonated());

        $assignedSubcategory = Subcategory::query()
            ->where('categoryDescription', 'CATEGORY A')
            ->where('subDescription', 'Office A')
            ->firstOrFail();
        $assignedSubcategory->update(['username' => $focalUser->username]);

        $focalUser->delete();

        $this->assertDatabaseMissing('users', ['id' => $focalUser->getKey()]);
        $this->assertNull($assignedSubcategory->fresh()->username);
    }

    public function test_focal_user_can_manage_participants_only_in_assigned_subcategories(): void
    {
        $focalUser = $this->createUser('Category A Focal', 'category-a-focal');
        $focalUser->update(['assigned_category' => 'CATEGORY A']);
        $focalUser->assignRole(Role::findByName('focal user'));

        $assignedSubcategory = $this->createSubcategory('CATEGORY A', 'Office A');
        $assignedSubcategory->update(['username' => $focalUser->username]);
        $this->createSubcategory('CATEGORY A', 'Office A Two');
        $this->createSubcategory('CATEGORY B', 'Office B');

        $assignedParticipant = $this->createParticipant(
            'Assigned',
            'CATEGORY A',
            'Office A',
        );
        $sameCategoryParticipant = $this->createParticipant(
            'Same Category',
            'CATEGORY A',
            'Office A Two',
        );
        $otherCategoryParticipant = $this->createParticipant(
            'Other Category',
            'CATEGORY B',
            'Office B',
        );

        $visibleParticipants = ParticipantsTable::applyVisibilityForUser(
            Participant::query(),
            $focalUser,
        )->get();

        $this->assertCount(1, $visibleParticipants);
        $this->assertSame($assignedParticipant->getKey(), $visibleParticipants->first()->getKey());
        $this->assertTrue($focalUser->canManageParticipantData('CATEGORY A', 'Office A'));
        $this->assertFalse($focalUser->canManageParticipantData('CATEGORY A', 'Office A Two'));
        $this->assertFalse($focalUser->canManageParticipantData('CATEGORY B', 'Office B'));

        $policy = new ParticipantPolicy();

        $this->assertTrue($policy->viewAny($focalUser));
        $this->assertTrue($policy->create($focalUser));
        $this->assertTrue($policy->view($focalUser, $assignedParticipant));
        $this->assertTrue($policy->update($focalUser, $assignedParticipant));
        $this->assertTrue($policy->delete($focalUser, $assignedParticipant));
        $this->assertFalse($policy->view($focalUser, $sameCategoryParticipant));
        $this->assertFalse($policy->update($focalUser, $sameCategoryParticipant));
        $this->assertFalse($policy->delete($focalUser, $sameCategoryParticipant));
        $this->assertFalse($policy->view($focalUser, $otherCategoryParticipant));

        $this->assertFalse($focalUser->can('ForceDelete:Participant'));
        $focalUser->givePermissionTo(Permission::findOrCreate('ForceDelete:Participant'));
        $this->assertTrue($focalUser->can('ForceDelete:Participant'));
        $this->assertFalse($policy->forceDelete($focalUser, $assignedParticipant));

        $this->assertSame(
            ['CATEGORY A' => 'CATEGORY A'],
            ParticipantForm::categoryOptionsForUser($focalUser),
        );
        $this->assertSame(
            ['Office A' => 'Office A'],
            ParticipantForm::subcategoryOptionsForUser($focalUser, 'CATEGORY A'),
        );
        $this->assertSame([], ParticipantForm::subcategoryOptionsForUser($focalUser, 'CATEGORY B'));
        $this->assertFalse(ParticipantsTable::canUseAttendanceExports($focalUser));
        $this->assertSame([], ParticipantsTable::attendanceSubcategoryOptions($focalUser));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($focalUser);

        Livewire::test(ListParticipants::class)
            ->assertTableActionHidden('attendance_export')
            ->assertTableActionHidden('food_attendance_export');

        $this->assertSame(1, $assignedSubcategory->registeredParticipants());
        $this->assertSame(99, $assignedSubcategory->registrationSlotsRemaining());

        Livewire::test(ParticipantAllocationOverview::class)
            ->assertSee('Your Subcategory Participant Allocation')
            ->assertSee('CATEGORY A — Office A')
            ->assertSee('99 remaining')
            ->assertSee('100 allocated')
            ->assertSee('1 registered');
    }

    private function createUser(
        string $name,
        string $username,
        ?string $administeredCategory = null,
    ): User {
        $user = User::query()->create([
            'name' => $name,
            'username' => $username,
            'administered_category' => $administeredCategory,
            'email' => "{$username}@example.test",
            'password' => 'password',
        ]);

        if (filled($administeredCategory)) {
            $allocation = CategoryAllocation::query()->firstOrCreate(
                ['categoryDescription' => $administeredCategory],
                ['allocated_participants' => 0],
            );

            $user->administeredCategoryAllocations()->attach($allocation);
        }

        return $user;
    }

    private function createSubcategory(string $category, string $description): Subcategory
    {
        return Subcategory::query()->create([
            'categoryDescription' => $category,
            'subDescription' => $description,
            'nop' => 100,
        ]);
    }

    private function createParticipant(
        string $firstName,
        string $category,
        ?string $subcategory = null,
    ): Participant
    {
        return Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => uniqid('participant-', true),
            'firstName' => $firstName,
            'lastName' => 'Runner',
            'categoryDescription' => $category,
            'subDescription' => $subcategory
                ?? ($category === 'CATEGORY A' ? 'Office A' : 'Office B'),
            'shirtSize' => 'medium',
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'waiver' => true,
            'distanceCategory' => '5km',
            'referenceNumber' => '0',
            'address' => 'Test address',
            'contactNumber' => '09123456789',
            'created_by' => 'another-user',
        ]);
    }
}
