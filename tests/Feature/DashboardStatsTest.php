<?php

namespace Tests\Feature;

use App\Filament\Pages\Dashboard;
use App\Models\CategoryAllocation;
use App\Models\Subcategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_total_allotment_includes_the_open_category_main_allocation_once(): void
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
    }
}
