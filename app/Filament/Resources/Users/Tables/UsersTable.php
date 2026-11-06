<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use STS\FilamentImpersonate\Actions\Impersonate;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('mobile_user_summary')
                    ->label('User')
                    ->getStateUsing(fn (User $record): string => $record->name)
                    ->description(fn (User $record): string => collect([
                        $record->office,
                        filled($record->username) ? "@{$record->username}" : null,
                        $record->roles->pluck('name')->join(', '),
                        self::categoryAssignment($record),
                    ])->filter()->join(' • '))
                    ->weight('bold')
                    ->wrap()
                    ->hiddenFrom('md'),
                TextColumn::make('name')
                    ->label('User')
                    ->description(fn (User $record): string => collect([
                        $record->office,
                        filled($record->username) ? "@{$record->username}" : null,
                    ])->filter()->join(' • '))
                    ->searchable(['name', 'office', 'username', 'email', 'cellphone_number'])
                    ->sortable()
                    ->wrap()
                    ->visibleFrom('md'),
                TextColumn::make('roles.name')
                    ->label('Role')
                    ->badge()
                    ->limitList(2)
                    ->expandableLimitedList()
                    ->placeholder('No role')
                    ->visibleFrom('md'),
                TextColumn::make('category_assignment')
                    ->label('Category Assignment')
                    ->getStateUsing(fn (User $record): string => self::categoryAssignment($record))
                    ->placeholder('Not assigned')
                    ->wrap()
                    ->visibleFrom('lg'),
                TextColumn::make('contact_details')
                    ->label('Contact')
                    ->getStateUsing(fn (User $record): string => collect([
                        $record->email,
                        $record->cellphone_number,
                    ])->filter()->join(' • '))
                    ->placeholder('Not provided')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->iconButton()
                    ->tooltip('Edit user'),
                DeleteAction::make()
                    ->iconButton()
                    ->tooltip('Delete user'),
                Impersonate::make()
                    ->iconButton()
                    ->tooltip('Impersonate user')
                    ->visible(function (User $record): bool {
                        $user = Auth::user();

                        return $record->canBeImpersonated() && ($user?->hasAnyRole([
                            'admin',
                            'superadmin',
                            'super_admin',
                        ])
                            || ($user?->canImpersonateFocalUser($record) ?? false));
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => Auth::user()?->hasAnyRole([
                            'admin',
                            'superadmin',
                            'super_admin',
                        ]) ?? false),
                ]),
            ]);
    }

    private static function categoryAssignment(User $user): string
    {
        if ($user->isAdministrativeOfficer()) {
            return $user->administeredCategoryAllocations
                ->pluck('categoryDescription')
                ->sort()
                ->join(', ');
        }

        if ($user->hasRole('focal user')) {
            return (string) $user->assigned_category;
        }

        return '';
    }
}
