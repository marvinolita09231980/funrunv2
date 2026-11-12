<?php

namespace Tests\Feature;

use App\Filament\Resources\Subcategories\Pages\CreateSubcategories;
use App\Filament\Resources\Subcategories\Schemas\SubcategoriesForm;
use App\Models\CategoryAllocation;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SubcategoryFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_category_options_use_category_names_from_category_allocations(): void
    {
        CategoryAllocation::query()->create([
            'categoryDescription' => 'CATEGORY B',
            'allocated_participants' => 10,
        ]);
        CategoryAllocation::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'allocated_participants' => 20,
        ]);

        Subcategory::query()->create([
            'categoryDescription' => 'LEGACY CATEGORY',
            'subDescription' => 'Legacy Office',
            'nop' => 5,
        ]);

        $this->assertSame([
            'CATEGORY A' => 'CATEGORY A',
            'CATEGORY B' => 'CATEGORY B',
        ], SubcategoriesForm::categoryOptions());
    }

    public function test_assigned_administrative_officer_is_valid_when_creating_a_subcategory(): void
    {
        $allocation = CategoryAllocation::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'allocated_participants' => 20,
        ]);

        $officer = User::factory()->create(['username' => 'ao-one']);
        $officer->assignRole(Role::findOrCreate('administrative officer', 'web'));
        $allocation->administrativeOfficers()->attach($officer);

        $this->assertTrue(SubcategoriesForm::isFocalUserEligibleForCategory(
            $officer->username,
            'CATEGORY A',
        ));

        $superadmin = User::factory()->create();
        $superadmin->assignRole(Role::findOrCreate('superadmin', 'web'));

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $this->actingAs($superadmin);

        Livewire::test(CreateSubcategories::class)
            ->fillForm([
                'categoryDescription' => 'CATEGORY A',
                'nop' => 10,
                'subDescription' => 'Office A',
                'username' => $officer->username,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('subcategories', [
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'username' => 'ao-one',
        ]);
    }
}
