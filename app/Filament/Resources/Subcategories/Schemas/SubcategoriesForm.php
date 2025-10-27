<?php

namespace App\Filament\Resources\Subcategories\Schemas;

use App\Models\User;
use App\Models\Subcategory;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class SubcategoriesForm
{
    public static function configure(Schema $schema): Schema
    {
         return $schema
            ->components([
                TextInput::make('categoryDescription')
                    ->required()
                    ->autocapitalize('words')
                    ->autocomplete(),
                TextInput::make('nop')
                    ->label('Maximum participants') 
                    ->numeric(),
                TextInput::make('subDescription')
                    ->required(),
                Select::make('username')
                    ->label('Assign this to User')
                    ->options(User::query()->distinct('username')->pluck('name', 'username'))
                    ->searchable()
                    ->reactive()
                    ->required(),
                   
            ]);
    }


   

}
