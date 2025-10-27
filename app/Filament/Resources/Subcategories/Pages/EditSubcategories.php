<?php

namespace App\Filament\Resources\Subcategories\Pages;

use App\Filament\Resources\Subcategories\SubcategoriesResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSubcategories extends EditRecord
{
    protected static string $resource = SubcategoriesResource::class;

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
