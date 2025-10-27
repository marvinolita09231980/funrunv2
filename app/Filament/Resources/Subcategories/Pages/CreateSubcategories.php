<?php

namespace App\Filament\Resources\Subcategories\Pages;

use App\Models\Subcategory;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Subcategories\SubcategoriesResource;

class CreateSubcategories extends CreateRecord
{
    protected static string $resource = SubcategoriesResource::class;
    

   


    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
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
