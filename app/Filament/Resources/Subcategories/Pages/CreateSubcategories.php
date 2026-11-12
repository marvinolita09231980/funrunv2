<?php

namespace App\Filament\Resources\Subcategories\Pages;

use App\Filament\Resources\Subcategories\Schemas\SubcategoriesForm;
use App\Filament\Resources\Subcategories\SubcategoriesResource;
use App\Models\CategoryAllocation;
use App\Models\Subcategory;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateSubcategories extends CreateRecord
{
    protected static string $resource = SubcategoriesResource::class;

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        $allocation = CategoryAllocation::query()
            ->where('categoryDescription', $data['categoryDescription'])
            ->first();

        if (! $allocation) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.categoryDescription' => 'Set the main-category allocation before creating subcategories.',
            ]);
        }

        $capacity = (int) $data['nop'];

        if (! $allocation->accommodatesSubcategoryCapacity($capacity)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.nop' => 'This capacity exceeds the remaining main-category allocation.',
            ]);
        }

        if (
            ! ($capacity === 0 && blank($data['username'] ?? null))
            && ! SubcategoriesForm::isFocalUserEligibleForCategory(
                $data['username'] ?? null,
                $data['categoryDescription'],
            )
        ) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'data.username' => 'Select an eligible focal user or Administrative Officer assigned to this main category.',
            ]);
        }

        $exists = Subcategory::where('categoryDescription', $data['categoryDescription'])
            ->where('subDescription', $data['subDescription'])
            ->exists();

        if ($exists) {
            Notification::make()
                ->warning()
                ->title('Duplicate Entry')
                ->body('This Category already exists.')
                ->persistent()
                ->send();
            $this->halt();
        }

    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        Notification::make()
            ->success()
            ->title('Subcategory created successfully')
            ->send();

    }
}
