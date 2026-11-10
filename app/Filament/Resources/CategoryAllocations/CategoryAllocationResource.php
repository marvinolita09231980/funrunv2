<?php

namespace App\Filament\Resources\CategoryAllocations;

use App\Filament\Resources\CategoryAllocations\Pages\CreateCategoryAllocation;
use App\Filament\Resources\CategoryAllocations\Pages\EditCategoryAllocation;
use App\Filament\Resources\CategoryAllocations\Pages\ListCategoryAllocations;
use App\Filament\Resources\CategoryAllocations\Schemas\CategoryAllocationForm;
use App\Filament\Resources\CategoryAllocations\Tables\CategoryAllocationsTable;
use App\Models\CategoryAllocation;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CategoryAllocationResource extends Resource
{
    protected static ?string $model = CategoryAllocation::class;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Category Allocations';

    protected static string|\UnitEnum|null $navigationGroup = 'Event Operations';

    protected static ?int $navigationSort = 10;

    protected static ?string $modelLabel = 'Category Allocation';

    public static function form(Schema $schema): Schema
    {
        return CategoryAllocationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CategoryAllocationsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return self::applyVisibilityForUser(
            parent::getEloquentQuery()
                ->with('administrativeOfficers')
                ->withSum('subcategories', 'nop'),
            Auth::user(),
        );
    }

    public static function applyVisibilityForUser(Builder $query, ?User $user): Builder
    {
        if (! $user?->isAdministrativeOfficer()) {
            return $query;
        }

        return $query->whereHas(
            'administrativeOfficers',
            fn (Builder $officerQuery): Builder => $officerQuery->whereKey($user->getKey()),
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCategoryAllocations::route('/'),
            'create' => CreateCategoryAllocation::route('/create'),
            'edit' => EditCategoryAllocation::route('/{record}/edit'),
        ];
    }
}
