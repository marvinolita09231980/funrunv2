<?php

namespace App\Filament\Resources\Subcategories\Schemas;

use App\Models\User;
use App\Models\Subcategory;
use App\Models\CategoryAllocation;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Utilities\Get;

class SubcategoriesForm
{
    public static function configure(Schema $schema): Schema
    {
         return $schema
            ->components([
                TextInput::make('categoryDescription')
                    ->required()
                    ->disabled(fn (): bool => Auth::user()?->isAdministrativeOfficer() ?? false)
                    ->autocapitalize('words')
                    ->autocomplete(),
                TextInput::make('nop')
                    ->label('Maximum Participants')
                    ->numeric()
                    ->minValue(fn (?Subcategory $record): int => $record?->minimumCapacity() ?? 0)
                    ->helperText(function (Get $get, ?Subcategory $record): ?string {
                        $allocation = CategoryAllocation::query()
                            ->where('categoryDescription', $get('categoryDescription'))
                            ->first();

                        if (! $allocation) {
                            return 'Set the main-category allocation before assigning subcategory capacity.';
                        }

                        $available = $allocation->allocated_participants
                            - $allocation->distributedParticipants($record?->getKey());

                        $minimum = $record?->minimumCapacity() ?? 0;

                        return "Main-category allocation: {$allocation->allocated_participants}. Available for this subcategory: "
                            . max(0, $available)
                            . ". Minimum allowed: {$minimum}.";
                    })
                    ->required(),
                TextInput::make('subDescription')
                    ->required()
                    ->disabled(fn (): bool => Auth::user()?->isAdministrativeOfficer() ?? false),
                Select::make('username')
                    ->label('Assigned Focal User')
                    ->options(function (Get $get): array {
                        $category = $get('categoryDescription');

                        if (blank($category)) {
                            return [];
                        }

                        return self::focalUserOptionsForCategory($category);
                    })
                    ->searchable()
                    ->reactive()
                    ->required(),
                   
            ]);
    }

    public static function focalUserOptionsForCategory(?string $category): array
    {
        if (blank($category)) {
            return [];
        }

        return User::role('focal user')
            ->where('assigned_category', $category)
            ->orderBy('name')
            ->pluck('name', 'username')
            ->all();
    }


   

}
