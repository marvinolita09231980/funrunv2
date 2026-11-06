<?php

namespace App\Filament\Resources\Subcategories\Pages;

use App\Models\Subcategory;
use App\Models\CategoryAllocation;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Subcategories\SubcategoriesResource;

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

        $focalUser = User::query()->where('username', $data['username'] ?? null)->first();

        if (
            ! ($capacity === 0 && blank($data['username'] ?? null))
            && ! $focalUser?->canBeAssignedToSubcategory($data['categoryDescription'])
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
