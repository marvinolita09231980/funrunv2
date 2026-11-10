<?php

namespace Tests\Feature;

use App\Filament\Pages\AuditLogs;
use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ParticipantReports;
use App\Filament\Pages\PotentialDuplicates;
use App\Filament\Pages\RegistrationControl;
use App\Filament\Pages\ShirtSizes;
use App\Filament\Resources\CategoryAllocations\CategoryAllocationResource;
use App\Filament\Resources\Finishers\FinisherResource;
use App\Filament\Resources\Participants\ParticipantResource;
use App\Filament\Resources\Subcategories\SubcategoriesResource;
use App\Filament\Resources\Users\UserResource;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource;
use Filament\Facades\Filament;
use Tests\TestCase;

class NavigationGroupingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel(Filament::getPanel('admin'));
    }

    public function test_admin_navigation_items_are_grouped_by_function(): void
    {
        $this->assertSame([
            'Overview',
            'Registration',
            'Event Operations',
            'Reports',
            'Administration',
        ], Filament::getCurrentPanel()->getNavigationGroups());

        $this->assertSame('Overview', Dashboard::getNavigationGroup());

        $this->assertSame('Registration', ParticipantResource::getNavigationGroup());
        $this->assertSame('Registration', PotentialDuplicates::getNavigationGroup());
        $this->assertSame('Registration', RegistrationControl::getNavigationGroup());

        $this->assertSame('Event Operations', CategoryAllocationResource::getNavigationGroup());
        $this->assertSame('Event Operations', SubcategoriesResource::getNavigationGroup());
        $this->assertSame('Event Operations', FinisherResource::getNavigationGroup());

        $this->assertSame('Reports', ShirtSizes::getNavigationGroup());
        $this->assertSame('Reports', ParticipantReports::getNavigationGroup());

        $this->assertSame('Administration', UserResource::getNavigationGroup());
        $this->assertSame('Administration', RoleResource::getNavigationGroup());
        $this->assertSame('Administration', AuditLogs::getNavigationGroup());
    }

    public function test_items_have_predictable_order_within_each_group(): void
    {
        $this->assertLessThan(
            PotentialDuplicates::getNavigationSort(),
            ParticipantResource::getNavigationSort(),
        );
        $this->assertLessThan(
            RegistrationControl::getNavigationSort(),
            PotentialDuplicates::getNavigationSort(),
        );
        $this->assertLessThan(
            SubcategoriesResource::getNavigationSort(),
            CategoryAllocationResource::getNavigationSort(),
        );
        $this->assertLessThan(
            FinisherResource::getNavigationSort(),
            SubcategoriesResource::getNavigationSort(),
        );
        $this->assertLessThan(
            ParticipantReports::getNavigationSort(),
            ShirtSizes::getNavigationSort(),
        );
        $this->assertLessThan(
            AuditLogs::getNavigationSort(),
            UserResource::getNavigationSort(),
        );
    }
}
