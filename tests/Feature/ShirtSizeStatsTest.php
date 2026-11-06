<?php

namespace Tests\Feature;

use App\Filament\Pages\ShirtSizes;
use App\Models\CategoryAllocation;
use App\Models\Participant;
use App\Models\Subcategory;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShirtSizeStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_shirt_sizes_are_broken_down_by_race_distance(): void
    {
        $this->createParticipant('XS Three A', 'xs', '3km');
        $this->createParticipant('XS Three B', 'xs', '3k');
        $this->createParticipant('XS Five', 'xs', '5km');
        $this->createParticipant('XL Ten', 'xl', '10km');

        $stats = ShirtSizes::shirtSizeStatsForYear((int) now()->year);

        $this->assertSame(4, $stats['total']);
        $this->assertSame(2, $stats['distance_totals']['3km']);
        $this->assertSame(1, $stats['distance_totals']['5km']);
        $this->assertSame(1, $stats['distance_totals']['10km']);
        $this->assertSame(3, $stats['sizes']['xs']['count']);
        $this->assertSame(2, $stats['sizes']['xs']['distances']['3km']['count']);
        $this->assertSame(1, $stats['sizes']['xs']['distances']['5km']['count']);
        $this->assertSame(0, $stats['sizes']['xs']['distances']['10km']['count']);
        $this->assertSame(1, $stats['sizes']['xl']['distances']['10km']['count']);
        $this->assertSame(0, $stats['sizes']['small']['count']);
    }

    public function test_pdf_report_contains_all_categories_subcategories_sizes_and_distances(): void
    {
        CategoryAllocation::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'allocated_participants' => 10,
        ]);
        Subcategory::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office A',
            'nop' => 10,
        ]);
        Subcategory::query()->create([
            'categoryDescription' => 'CATEGORY A',
            'subDescription' => 'Office B',
            'nop' => 0,
        ]);

        $this->createParticipant('Medium Three', 'medium', '3km', 'CATEGORY A', 'Office A');
        $this->createParticipant('Large Five', 'large', '5km', 'CATEGORY A', 'Office A');

        $report = ShirtSizes::shirtSizeReportForYear((int) now()->year);
        $officeA = collect($report['groups'])->firstWhere('subcategory', 'Office A');
        $officeB = collect($report['groups'])->firstWhere('subcategory', 'Office B');

        $this->assertNotNull($officeA);
        $this->assertNotNull($officeB);
        $this->assertSame('CATEGORY A', $officeA['category']);
        $this->assertSame(1, $officeA['distances']['3km']['sizes']['medium']);
        $this->assertSame(1, $officeA['distances']['5km']['sizes']['large']);
        $this->assertSame(2, $officeA['total']);
        $this->assertSame(0, $officeB['total']);
        $this->assertSame(2, $report['grand_total']);

        $html = view('pdf.shirt-size-report', [
            'report' => $report,
            'logoDataUri' => null,
        ])->render();

        $this->assertStringContainsString('Shirt Size Inventory Report', $html);
        $this->assertStringContainsString('CATEGORY A', $html);
        $this->assertStringContainsString('Office A', $html);
        $this->assertStringContainsString('Rave Run (3K)', $html);
        $this->assertStringContainsString('Grand Total', $html);

        $pdf = Pdf::loadView('pdf.shirt-size-report', [
            'report' => $report,
            'logoDataUri' => null,
        ])->setPaper('a4', 'landscape')->output();

        $this->assertStringStartsWith('%PDF', $pdf);
    }

    private function createParticipant(
        string $firstName,
        string $shirtSize,
        string $distance,
        string $category = 'OPEN CATEGORY',
        string $subcategory = 'Individual',
    ): Participant {
        return Participant::query()->create([
            'year' => now()->year,
            'participantNumber' => uniqid('participant-', true),
            'firstName' => $firstName,
            'lastName' => 'Runner',
            'categoryDescription' => $category,
            'subDescription' => $subcategory,
            'shirtSize' => $shirtSize,
            'birthDate' => '1990-01-01',
            'gender' => 'male',
            'waiver' => true,
            'distanceCategory' => $distance,
            'referenceNumber' => '0',
            'address' => 'Test address',
            'contactNumber' => '09123456789',
            'created_by' => 'opencategory',
        ]);
    }
}
