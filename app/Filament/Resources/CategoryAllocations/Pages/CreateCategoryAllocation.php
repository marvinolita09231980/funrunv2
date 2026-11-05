<?php

namespace App\Filament\Resources\CategoryAllocations\Pages;

use App\Filament\Resources\CategoryAllocations\CategoryAllocationResource;
use App\Models\OverallAllocation;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;

class CreateCategoryAllocation extends CreateRecord
{
    protected static string $resource = CategoryAllocationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $allocation = (int) $data['allocated_participants'];
        $overall = OverallAllocation::current();

        if (! $overall->canAllocateToCategory($allocation)) {
            throw ValidationException::withMessages([
                'data.allocated_participants' => 'This allocation exceeds the '
                    . number_format($overall->remainingParticipants())
                    . ' participants remaining in the overall allocation.',
            ]);
        }

        return $data;
    }
}
