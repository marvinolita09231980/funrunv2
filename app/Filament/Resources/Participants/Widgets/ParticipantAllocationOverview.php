<?php

namespace App\Filament\Resources\Participants\Widgets;

use App\Models\CategoryAllocation;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ParticipantAllocationOverview extends StatsOverviewWidget
{
    protected ?string $pollingInterval = '10s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = Auth::user();

        return $user instanceof User
            && ($user->isAdministrativeOfficer() || $user->hasRole('focal user'));
    }

    protected function getHeading(): ?string
    {
        return Auth::user()?->hasRole('focal user')
            ? 'Your Subcategory Participant Allocation'
            : 'Your Category Participant Allocation';
    }

    protected function getDescription(): ?string
    {
        return Auth::user()?->hasRole('focal user')
            ? 'Current-year registrations are deducted automatically from your assigned subcategory.'
            : 'Current-year registrations are deducted automatically, including participants added by focal users.';
    }

    protected function getStats(): array
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return [];
        }

        $year = now()->year;

        if ($user->hasRole('focal user') && ! $user->isAdministrativeOfficer()) {
            return Subcategory::query()
                ->where('username', $user->username)
                ->orderBy('categoryDescription')
                ->orderBy('subDescription')
                ->get()
                ->map(function (Subcategory $subcategory) use ($year): Stat {
                    $registered = $subcategory->registeredParticipants($year);
                    $remaining = $subcategory->registrationSlotsRemaining($year);

                    return Stat::make(
                        $subcategory->categoryDescription . ' — ' . $subcategory->subDescription,
                        number_format($remaining) . ' remaining',
                    )
                        ->description(
                            number_format((int) $subcategory->nop)
                            . ' allocated · '
                            . number_format($registered)
                            . ' registered in '
                            . $year
                        )
                        ->descriptionIcon('heroicon-o-user-group')
                        ->color($remaining > 0 ? 'success' : 'danger');
                })
                ->all();
        }

        if (! $user->isAdministrativeOfficer()) {
            return [];
        }

        return $user->administeredCategoryAllocations()
            ->orderBy('categoryDescription')
            ->get()
            ->map(function (CategoryAllocation $allocation) use ($year): Stat {
                $registered = $allocation->registeredParticipants($year);
                $remaining = $allocation->registrationSlotsRemaining($year);

                return Stat::make($allocation->categoryDescription, number_format($remaining) . ' remaining')
                    ->description(
                        number_format($allocation->allocated_participants)
                        . ' allocated · '
                        . number_format($registered)
                        . ' registered in '
                        . $year
                    )
                    ->descriptionIcon('heroicon-o-user-group')
                    ->color($remaining > 0 ? 'success' : 'danger');
            })
            ->all();
    }
}
