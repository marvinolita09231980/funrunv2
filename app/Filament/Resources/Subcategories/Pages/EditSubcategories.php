<?php

namespace App\Filament\Resources\Subcategories\Pages;

use App\Filament\Resources\Subcategories\Schemas\SubcategoriesForm;
use App\Filament\Resources\Subcategories\SubcategoriesResource;
use App\Models\CategoryAllocation;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubcategories extends EditRecord
{
    protected static string $resource = SubcategoriesResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $allocation = CategoryAllocation::query()
            ->where('categoryDescription', $this->record->categoryDescription)
            ->first();

        if (! $allocation) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.nop' => 'Set the main-category allocation before changing subcategory capacity.',
            ]);
        }

        $capacity = (int) $data['nop'];
        $minimum = $this->record->minimumCapacity();

        if (! $this->record->canSetCapacity($capacity)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.nop' => "The minimum allowed capacity is {$minimum} because this subcategory currently has {$minimum} registered participant(s).",
            ]);
        }

        if (! $allocation->accommodatesSubcategoryCapacity(
            $capacity,
            $this->record->getKey(),
        )) {
            $available = $allocation->allocated_participants
                - $allocation->distributedParticipants($this->record->getKey())
                - $allocation->directRegisteredParticipants();

            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.nop' => 'This capacity exceeds the available main-category allocation of '.max(0, $available).'.',
            ]);
        }

        if (
            ! ($capacity === 0 && blank($data['username'] ?? null))
            && ! SubcategoriesForm::isFocalUserEligibleForCategory(
                $data['username'] ?? null,
                $this->record->categoryDescription,
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.username' => 'Select an eligible focal user or Administrative Officer assigned to this main category.',
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
    // protected function mutateFormDataBeforeSave(array $data): array
    // {

    //     return $this->data;
    // }
}
