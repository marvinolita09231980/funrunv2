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
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SubcategoriesResource extends Resource
{
    protected static ?string $model = Subcategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Event Operations';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'Categories';

    public static function form(Schema $schema): Schema
    {
        return SubcategoriesForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SubcategoriesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return self::applyVisibilityForUser(
            parent::getEloquentQuery()->with('focalUser'),
            Auth::user(),
        );
    }

    public static function applyVisibilityForUser(Builder $query, ?User $user): Builder
    {
        if (! $user?->isAdministrativeOfficer()) {
            return $query;
        }

        return $query->whereIn(
            'categoryDescription',
            $user->administeredCategoryNames(),
        );
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
