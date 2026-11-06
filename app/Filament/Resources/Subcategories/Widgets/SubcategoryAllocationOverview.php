<?php

namespace App\Filament\Resources\Subcategories\Widgets;

use App\Models\CategoryAllocation;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SubcategoryAllocationOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Category Allocation for Subcategories';

    protected ?string $description = 'Distributed capacity and free slots available for further subcategory allocation.';

    protected ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->isAdministrativeOfficer() || $user->hasAnyRole([
                'admin',
                'superadmin',
                'super_admin',
            ]));
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $allocations = $user->isAdministrativeOfficer()
            ? $user->administeredCategoryAllocations()->orderBy('categoryDescription')->get()
            : CategoryAllocation::query()->orderBy('categoryDescription')->get();

        return $allocations
            ->map(function (CategoryAllocation $allocation): Stat {
                $distributed = $allocation->distributedParticipants();
                $remaining = $allocation->remainingParticipants();

                return Stat::make(
                    $allocation->categoryDescription,
                    number_format($allocation->allocated_participants) . ' allocated',
                )
                    ->description(
                        number_format($distributed)
                        . ' allocated to subcategories · '
                        . number_format($remaining)
                        . ' free for allocation'
                        . ($allocation->directRegisteredParticipants() > 0
                            ? ' · ' . number_format($allocation->directRegisteredParticipants()) . ' registered directly under category'
                            : '')
                    )
                    ->descriptionIcon('heroicon-o-squares-plus')
                    ->color($remaining > 0 ? 'success' : 'danger');
            })
            ->all();
    }
}
