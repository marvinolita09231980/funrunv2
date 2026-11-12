<?php

namespace App\Filament\Resources\Subcategories\Schemas;

use App\Models\CategoryAllocation;
use App\Models\Subcategory;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SubcategoriesForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('categoryDescription')
                    ->label('Category Name')
                    ->options(fn (): array => self::categoryOptions())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(fn (Set $set) => $set('username', null))
                    ->required()
                    ->disabled(fn (): bool => Auth::user()?->isAdministrativeOfficer() ?? false),
                TextInput::make('nop')
                    ->label('Maximum Participants')
                    ->numeric()
                    ->live()
                    ->minValue(fn (?Subcategory $record): int => $record?->minimumCapacity() ?? 0)
                    ->helperText(function (Get $get, ?Subcategory $record): ?string {
                        $allocation = CategoryAllocation::query()
                            ->where('categoryDescription', $get('categoryDescription'))
                            ->first();

                        if (! $allocation) {
                            return 'Set the main-category allocation before assigning subcategory capacity.';
                        }

                        $available = $allocation->allocated_participants
                            - $allocation->distributedParticipants($record?->getKey())
                            - $allocation->directRegisteredParticipants();

                        $minimum = $record?->minimumCapacity() ?? 0;

                        return "Main-category allocation: {$allocation->allocated_participants}. Available for this subcategory: "
                            .max(0, $available)
                            .". Minimum allowed: {$minimum}.";
                    })
                    ->required(),
                TextInput::make('subDescription')
                    ->required()
                    ->disabled(fn (): bool => Auth::user()?->isAdministrativeOfficer() ?? false),
                Select::make('username')
                    ->label('Assigned Focal User or AO')
                    ->helperText('Optional when the allocation is 0. Administrative Officers assigned to this main category may also be selected.')
                    ->options(function (Get $get): array {
                        $category = $get('categoryDescription');

                        if (blank($category)) {
                            return [];
                        }

                        return self::focalUserOptionsForCategory($category);
                    })
                    ->searchable()
                    ->reactive()
                    ->required(fn (Get $get): bool => (int) ($get('nop') ?? 0) > 0),

            ]);
    }

    public static function categoryOptions(): array
    {
        return CategoryAllocation::query()
            ->orderBy('categoryDescription')
            ->pluck('categoryDescription', 'categoryDescription')
            ->all();
    }

    public static function focalUserOptionsForCategory(?string $category): array
    {
        if (blank($category)) {
            return [];
        }

        return self::eligibleFocalUsersForCategory($category)
            ->orderBy('name')
            ->pluck('name', 'username')
            ->all();
    }

    public static function isFocalUserEligibleForCategory(
        ?string $username,
        ?string $category,
    ): bool {
        if (blank($username) || blank($category)) {
            return false;
        }

        return self::eligibleFocalUsersForCategory($category)
            ->where('username', $username)
            ->exists();
    }

    private static function eligibleFocalUsersForCategory(string $category): Builder
    {
        return User::query()
            ->where(function ($query) use ($category): void {
                $query
                    ->where(function ($query) use ($category): void {
                        $query
                            ->where('assigned_category', $category)
                            ->whereHas(
                                'roles',
                                fn ($query) => $query->where('name', 'focal user'),
                            );
                    })
                    ->orWhere(function ($query) use ($category): void {
                        $query
                            ->whereHas(
                                'roles',
                                fn ($query) => $query->whereIn(
                                    'name',
                                    [
                                        'administrative officer',
                                        'ao',
                                        'admin',
                                        'superadmin',
                                        'super_admin',
                                    ],
                                ),
                            )
                            ->whereHas(
                                'administeredCategoryAllocations',
                                fn ($query) => $query->where(
                                    'categoryDescription',
                                    $category,
                                ),
                            );
                    });
            });
    }
}
