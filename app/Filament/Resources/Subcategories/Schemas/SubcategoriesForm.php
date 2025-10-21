<?php

namespace App\Filament\Resources\Subcategories\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class SubcategoriesForm
{
    public static function configure(Schema $schema): Schema
    {
         return $schema
            ->components([
                TextInput::make('categoryDescription')
                    ->required(),
                TextInput::make('nop')
                    ->label('Maximum participants') 
                    ->numeric(),
                TextInput::make('subDescription')
                    ->required(),
               
            ]);
    }
}
