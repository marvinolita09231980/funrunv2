<?php

namespace App\Filament\Resources\Subcategories;

use BackedEnum;
use Filament\Tables\Table;
use App\Models\Subcategory;
use Filament\Schemas\Schema;
use App\Models\Subcategories;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Subcategories\Pages\EditSubcategories;
use App\Filament\Resources\Subcategories\Pages\ListSubcategories;
use App\Filament\Resources\Subcategories\Pages\CreateSubcategories;
use App\Filament\Resources\Subcategories\Schemas\SubcategoriesForm;
use App\Filament\Resources\Subcategories\Tables\SubcategoriesTable;

class SubcategoriesResource extends Resource
{
    protected static ?string $model = Subcategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Categories';

    public static function form(Schema $schema): Schema
    {
        return SubcategoriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubcategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSubcategories::route('/'),
            'create' => CreateSubcategories::route('/create'),
            'edit' => EditSubcategories::route('/{record}/edit'),
        ];
    }
}
