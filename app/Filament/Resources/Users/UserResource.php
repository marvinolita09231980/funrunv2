<?php

namespace App\Filament\Resources\Users;

use BackedEnum;
use App\Models\User;
use App\Models\CategoryAllocation;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Spatie\Permission\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserResource extends Resource
{
   
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Administration';

    protected static ?int $navigationSort = 10;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->columns(1)->schema([
            TextInput::make('name')
                ->label('Full Name')
                ->helperText('Enter the first and last name. The username is generated automatically.')
                ->required()
                ->live()
                ->afterStateUpdated(
                    fn (?string $state, Set $set, ?User $record): mixed => $record
                        ? null
                        : $set('username', User::usernameFromName($state)),
                ),
            TextInput::make('office')
                ->required()
                ->maxLength(255),
            TextInput::make('cellphone_number')
                ->label('Cellphone Number')
                ->tel()
                ->maxLength(20),
            Select::make('assigned_category')
                ->label('Focal User Category')
                ->helperText('A focal user can only be assigned to subcategories in this category.')
                ->options(function (): array {
                    $query = CategoryAllocation::query()
                        ->orderBy('categoryDescription');

                    if (Auth::user()?->isAdministrativeOfficer()) {
                        $query->whereIn(
                            'categoryDescription',
                            Auth::user()->administeredCategoryNames(),
                        );
                    }

                    return $query
                        ->pluck('categoryDescription', 'categoryDescription')
                        ->all();
                })
                ->default(function (): ?string {
                    if (! Auth::user()?->isAdministrativeOfficer()) {
                        return null;
                    }

                    $categories = Auth::user()->administeredCategoryNames();

                    return count($categories) === 1 ? $categories[0] : null;
                })
                ->searchable()
                ->required(fn (Get $get): bool => (Auth::user()?->isAdministrativeOfficer() ?? false)
                    || Role::query()
                        ->whereIn('id', (array) $get('roles'))
                        ->where('name', 'focal user')
                        ->exists()),
            TextInput::make('email')
                ->label('Email address')
                ->email()
                ->unique(ignoreRecord: true)
                ->dehydrateStateUsing(
                    fn (?string $state): ?string => filled($state) ? trim($state) : null,
                ),
            TextInput::make('username')
                ->readOnly()
                ->helperText('Lowercase full name without spaces.')
                ->unique(ignoreRecord: true),
            TextInput::make('password')
                ->password()
                ->revealable()
                ->autocomplete('new-password')
                ->formatStateUsing(fn (): null => null)
                ->required(fn (?User $record): bool => blank($record))
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->label(fn (?User $record): string => $record ? 'New Password' : 'Password')
                ->helperText(fn (?User $record): string => $record
                    ? 'Leave blank to keep the current password. Use the eye icon to show or hide a new password while typing.'
                    : 'Use the eye icon to show or hide the password while typing.'),

            // 👇 ADD THIS HERE
            Select::make('roles')
                ->relationship('roles', 'name')
                ->multiple()
                ->preload()
                ->searchable()
                ->live()
                ->hidden(fn (): bool => Auth::user()?->isAdministrativeOfficer() ?? false)
                ->label('Roles'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return self::applyVisibilityForUser(
            parent::getEloquentQuery()->with(['roles', 'administeredCategoryAllocations']),
            Auth::user(),
        );
    }

    public static function applyVisibilityForUser(Builder $query, ?User $user): Builder
    {
        if ($user?->isAdminWithoutSuperAdmin()) {
            return $query->whereDoesntHave(
                'roles',
                fn (Builder $query): Builder => $query->whereIn(
                    'name',
                    ['superadmin', 'super_admin'],
                ),
            );
        }

        if (! $user?->isAdministrativeOfficer()) {
            return $query;
        }

        return $query
            ->whereIn('assigned_category', $user->administeredCategoryNames())
            ->whereHas('roles', fn (Builder $query): Builder => $query->where('name', 'focal user'));
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
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
