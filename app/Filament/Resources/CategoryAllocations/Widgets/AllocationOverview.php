<?php

namespace App\Filament\Resources\CategoryAllocations\Widgets;

use App\Models\OverallAllocation;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AllocationOverview extends StatsOverviewWidget
{
    protected ?string $heading = 'Overall Participant Allocation';

    protected function getStats(): array
    {
        $allocation = OverallAllocation::current();
        $assigned = $allocation->assignedParticipants();
        $remaining = $allocation->remainingParticipants();

        return [
            Stat::make('Overall Allocation', number_format($allocation->total_participants)),
            Stat::make('Assigned to Categories', number_format($assigned)),
            Stat::make('Remaining', number_format($remaining))
                ->color($remaining > 0 ? 'success' : 'danger'),
        ];
    }
}
