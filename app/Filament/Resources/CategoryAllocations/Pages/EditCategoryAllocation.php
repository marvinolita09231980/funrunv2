<?php

namespace App\Filament\Resources\CategoryAllocations\Pages;

use App\Filament\Resources\CategoryAllocations\CategoryAllocationResource;
use App\Models\OverallAllocation;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditCategoryAllocation extends EditRecord
{
    protected static string $resource = CategoryAllocationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $distributed = $this->record->distributedParticipants();
        $allocation = (int) $data['allocated_participants'];

        if (! $this->record->canSetAllocation($allocation)) {
            throw ValidationException::withMessages([
                'data.allocated_participants' => "Allocation cannot be lower than the {$distributed} participants already distributed among subcategories.",
            ]);
        }

        $overall = OverallAllocation::current();

        if (! $overall->canAllocateToCategory($allocation, $this->record->getKey())) {
            throw ValidationException::withMessages([
                'data.allocated_participants' => 'This allocation exceeds the '
                    . number_format($overall->availableForCategory($this->record->getKey()))
                    . ' participants available for this category from the overall allocation.',
            ]);
        }

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
