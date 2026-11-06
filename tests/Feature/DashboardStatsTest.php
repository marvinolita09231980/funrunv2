<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\CategoryAllocation;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_allocation_includes_the_open_category_main_allocation_once(): void
    {
        CategoryAllocation::query()->create([
            'categoryDescription' => 'OPEN CATEGORY',
            'allocated_participants' => 10,
        ]);
        CategoryAllocation::query()->create([
            'categoryDescription' => 'PLGU',
            'allocated_participants' => 100,
        ]);

        Subcategory::query()->create([
            'categoryDescription' => 'PLGU',
            'subDescription' => 'Office One',
            'nop' => 60,
        ]);
        Subcategory::query()->create([
            'categoryDescription' => 'PLGU',
            'subDescription' => 'Office Two',
            'nop' => 20,
        ]);

        $this->assertDatabaseMissing('subcategories', [
            'categoryDescription' => 'OPEN CATEGORY',
        ]);

        $dashboard = app(Dashboard::class);
        $dashboard->selectedYear = now()->year;
        $stats = $dashboard->getDashboardStats();

        $this->assertSame(90, $stats['allocated']);
        $this->assertSame(90, $stats['available']);
        $this->assertSame(0, $stats['capacity_percentage']);

        $breakdown = $dashboard->getAllocationBreakdown();
        $this->assertSame(90, $breakdown['total']);
        $this->assertCount(2, $breakdown['groups']);
        $plgu = collect($breakdown['groups'])->firstWhere('category', 'PLGU');
        $this->assertSame(80, $plgu['total']);
        $this->assertSame([60, 20], array_column($plgu['items'], 'amount'));

        $user = User::factory()->create();
        $user->assignRole(Role::findByName('superadmin', 'web'));
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Dashboard::class)
            ->call('openAllocationBreakdown')
            ->assertSet('showAllocationBreakdown', true)
            ->assertDispatched('open-modal', id: 'distributed-allocation-breakdown')
            ->assertSee('Distributed Allocation Breakdown')
            ->assertSee('Office One')
            ->assertSee('Office Two')
            ->assertSee('No subcategories');
    }
}
